<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Listener;

use Psr\Log\LoggerInterface;
use RabbitEvents\Bundle\Consumer;
use RabbitEvents\Bundle\Exception\ConnectionLostException;
use RabbitEvents\Bundle\Exception\MaxAttemptsExceededException;
use RabbitEvents\Bundle\Exception\TimeoutExceededException;
use RabbitEvents\Bundle\Listener\Event\MessageProcessingFailed;
use RabbitEvents\Bundle\Listener\Event\WorkerStopping;
use RabbitEvents\Bundle\Listener\Handler\Processor;
use RabbitEvents\Bundle\Message\Envelope;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class Worker
{
    public bool $shouldQuit = false;

    public function __construct(
        protected LoggerInterface $logger,
        protected EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * Run the worker loop.
     */
    public function work(Processor $processor, Consumer $consumer, ListenerOptions $options): WorkerExitStatus
    {
        while (true) {
            try {
                $message = $consumer->nextMessage(1000);
            } catch (ConnectionLostException $e) {
                $this->logger->error($e->getMessage(), ['exception' => $e]);
                $this->shouldQuit = true;
                $message = null;
            }

            if ($message) {
                try {
                    if ($this->exceedsMaxAttempts($message, $options)) {
                        $exception = new MaxAttemptsExceededException(
                            sprintf(
                                'Message [%s] has been attempted too many times (%d attempts)',
                                $message->event,
                                $message->attempts()
                            )
                        );

                        $this->eventDispatcher->dispatch(new MessageProcessingFailed($message, $exception));
                    } else {
                        $this->registerTimeoutHandler($message, $options);

                        $processor->process($message, $options);
                    }
                } catch (\Throwable $e) {
                    $this->logger->error($e->getMessage(), ['exception' => $e]);
                } finally {
                    $consumer->acknowledge($message);
                    $this->resetTimeoutHandler();
                }
            }

            if ($status = $this->stopIfNecessary($options)) {
                $this->eventDispatcher->dispatch(new WorkerStopping($status->value));

                return $status;
            }
        }
    }

    protected function exceedsMaxAttempts(Envelope $message, ListenerOptions $options): bool
    {
        return $options->maxTries > 0 && $message->attempts() > $options->maxTries;
    }

    protected function stopIfNecessary(ListenerOptions $options): ?WorkerExitStatus
    {
        if ($this->shouldQuit) {
            return WorkerExitStatus::SUCCESS;
        }

        if ($this->memoryExceeded($options->memory)) {
            return WorkerExitStatus::MEMORY_LIMIT;
        }

        return null;
    }

    protected function memoryExceeded(int $memoryLimit): bool
    {
        return (memory_get_usage(true) / 1024 / 1024) >= $memoryLimit;
    }



    protected function registerTimeoutHandler(Envelope $message, ListenerOptions $options): void
    {
        if (!extension_loaded('pcntl') || $options->timeout <= 0) {
            return;
        }

        pcntl_signal(SIGALRM, function () use ($message, $options) {
            $exception = new TimeoutExceededException(
                sprintf(
                    'Processing [%s] timed out after %d seconds',
                    $message->event,
                    $options->timeout
                )
            );

            $this->eventDispatcher->dispatch(new MessageProcessingFailed($message, $exception));

            $this->kill(WorkerExitStatus::ERROR);
        });

        pcntl_alarm($options->timeout);
    }

    protected function resetTimeoutHandler(): void
    {
        if (!extension_loaded('pcntl')) {
            return;
        }

        pcntl_alarm(0);
    }

    /**
     * Kill the worker process.
     */
    public function kill(WorkerExitStatus|int $status = 0): void
    {
        $exitCode = $status instanceof WorkerExitStatus ? $status->value : $status;

        $this->eventDispatcher->dispatch(new WorkerStopping($exitCode));

        exit($exitCode);
    }
}
