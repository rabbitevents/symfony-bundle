<?php

namespace RabbitEvents\Bundle\Tests\Publisher;

use Mockery as m;
use RabbitEvents\Bundle\Contract\Transport;
use RabbitEvents\Bundle\Message\Envelope;
use RabbitEvents\Bundle\Publisher\MessageFactory;
use RabbitEvents\Bundle\Publisher;
use RabbitEvents\Bundle\Publisher\ShouldPublish;
use RabbitEvents\Bundle\Tests\TestCase;

class PublisherTest extends TestCase
{
    public function testPublish(): void
    {
        $event = new SomeEvent(['foo' => 'bar']);

        $messageMock = m::mock(Envelope::class);

        $messageFactory = m::mock(MessageFactory::class);
        $messageFactory->shouldReceive()
            ->create($event)
            ->andReturn($messageMock);
        $sender = m::mock(Transport::class);
        $sender->shouldReceive()
            ->send($messageMock)
            ->once();

        $publisher = new Publisher($messageFactory, $sender);
        $publisher->publish($event);
    }
}

class SomeEvent implements ShouldPublish
{
    private array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function publishEventKey(): string
    {
        return 'something.happened';
    }

    public function toPublish(): array
    {
        return $this->payload;
    }
}
