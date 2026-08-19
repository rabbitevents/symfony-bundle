<?php

namespace RabbitEvents\Bundle\Tests\Listener;

use Mockery as m;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use Psr\Log\LoggerInterface;
use RabbitEvents\Bundle\Consumer;
use RabbitEvents\Bundle\Exception\ConnectionLostException;
use RabbitEvents\Bundle\Listener\Event\MessageProcessingFailed;
use RabbitEvents\Bundle\Listener\Event\WorkerStopping;
use RabbitEvents\Bundle\Listener\ListenerOptions;
use RabbitEvents\Bundle\Listener\Handler\Processor;
use RabbitEvents\Bundle\Listener\Worker;
use RabbitEvents\Bundle\Listener\WorkerExitStatus;
use RabbitEvents\Bundle\Message\Envelope;
use RabbitEvents\Bundle\Contract\Payload;
use RabbitEvents\Bundle\Contract\TransportMessage;
use RabbitEvents\Bundle\Tests\Support\MocksCommonObjects;
use RabbitEvents\Bundle\Tests\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use RabbitEvents\Bundle\Tests\Support\Stubs\TransportMessageStub;

class WorkerTest extends TestCase
{
    use MocksCommonObjects;

    public EventDispatcherInterface $events;
    public LoggerInterface $logger;

    private ListenerOptions $options;

    #[Before]
    protected function setUp(): void
    {
        $this->events = m::spy(EventDispatcherInterface::class);
        $this->logger = m::spy(LoggerInterface::class);
        $this->options = $this->createListenerOptions();
    }

    public function testInstantiable(): void
    {
        self::assertInstanceOf(Worker::class, new Worker($this->logger, $this->events));
    }

    public function testWork(): void
    {
        $worker = new Worker($this->logger, $this->events);
        $worker->shouldQuit = true; // For one tick only

        $processor = m::spy(Processor::class);

        // Create real Envelope with mocked TransportMessage
        $transportMessage = m::mock(TransportMessage::class);
        $transportMessage->shouldReceive('getProperty')->with('x-attempts', 0)->andReturn(1);
        $transportMessage->shouldReceive('getProperty')->with('event')->andReturn('test.event');
        $transportMessage->shouldReceive('getProperties')->andReturn([]);
        $transportMessage->shouldReceive('getOrigin')->andReturn(null);

        $message = new Envelope('test.event', m::mock(Payload::class));
        $message->setTransportMessage($transportMessage);

        $consumer = m::mock(Consumer::class)->makePartial();
        $consumer->shouldReceive('nextMessage')
            ->andReturn($message);
        $consumer->shouldReceive('acknowledge')->once();

        $status = $worker->work($processor, $consumer, $this->options);

        self::assertEquals(WorkerExitStatus::SUCCESS, $status);

        $processor->shouldHaveReceived()->process($message, $this->options);
        $this->events->shouldHaveReceived()->dispatch(m::type(WorkerStopping::class))->once();
    }

    public function testStopIfMemoryLimitExceeded(): void
    {
        $worker = new Worker($this->logger, $this->events);
        $options = $this->createListenerOptions(['memory' => 0]);

        $consumer = m::mock(Consumer::class);
        $consumer->shouldReceive('nextMessage')
            ->andReturn(new Envelope('test', m::mock(Payload::class)));
        $consumer->shouldReceive('acknowledge')->once();

        $status = $worker->work(m::spy(Processor::class), $consumer, $options);

        self::assertEquals(WorkerExitStatus::MEMORY_LIMIT, $status);
        $this->events->shouldHaveReceived()->dispatch(m::type(WorkerStopping::class))->once();
    }

    public function testStopListeningIfLostConnection(): void
    {
        $exception = new ConnectionLostException();

        $worker = new Worker($this->logger, $this->events);

        $consumer = m::mock(Consumer::class);
        $consumer->shouldReceive('nextMessage')
            ->andThrow($exception);

        $status = $worker->work(m::mock(Processor::class), $consumer, $this->options);

        self::assertEquals(WorkerExitStatus::SUCCESS, $status);

        $this->logger->shouldHaveReceived()->error($exception->getMessage(), m::any());
        $this->events->shouldHaveReceived()->dispatch(m::type(WorkerStopping::class))->once();
    }

    public function testFinallyAcknowledge(): void
    {
        $exception = new \RuntimeException();

        $worker = new Worker($this->logger, $this->events);
        $worker->shouldQuit = true; // For one tick only

        $processor = m::mock(Processor::class);
        $processor->shouldReceive('process')
            ->andThrow($exception);

        $message = new Envelope('test.event', m::mock(Payload::class));
        $transportMessage = m::mock(TransportMessage::class);
        $transportMessage->shouldReceive('getProperty')->with('x-attempts', 0)->andReturn(1);
        $message->setTransportMessage($transportMessage);

        $consumer = m::mock(Consumer::class);
        $consumer->shouldReceive('nextMessage')
            ->andReturn($message);

        $consumer->shouldReceive()
            ->acknowledge($message);

        $status = $worker->work($processor, $consumer, $this->options);

        self::assertEquals(WorkerExitStatus::SUCCESS, $status);

        $this->logger->shouldHaveReceived()->error($exception->getMessage(), m::any());
        $this->events->shouldHaveReceived()->dispatch(m::type(WorkerStopping::class))->once();
    }

    public function testProcessNotStartedIfExceededMaxAttempts()
    {
        $transportMessage = new TransportMessageStub(
            json_encode(['data']),
            ['event' => 'test.event', 'x-attempts' => 3]
        );
        $message = Envelope::createFromTransportMessage($transportMessage);

        $consumer = m::mock(Consumer::class);
        $consumer->shouldReceive()
            ->nextMessage(1000)
            ->andReturn($message);
        $consumer->shouldReceive('acknowledge')->once();

        $processor = m::spy(Processor::class);

        $worker = new Worker($this->logger, $this->events);
        $worker->shouldQuit = true; // one tick

        $options = $this->createListenerOptions(['maxTries' => 2]);

        $worker->work($processor, $consumer, $options);

        $processor->shouldNotHaveReceived('process');

        $this->events->shouldHaveReceived()->dispatch(m::type(MessageProcessingFailed::class))->once();
    }
}
