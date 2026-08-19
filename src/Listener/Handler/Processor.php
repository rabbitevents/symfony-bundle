<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Listener\Handler;

use Psr\Log\LoggerInterface;
use RabbitEvents\Bundle\Exception\FailedException;
use RabbitEvents\Bundle\Listener\Event\ListenerHandled;
use RabbitEvents\Bundle\Listener\Event\ListenerHandleFailed;
use RabbitEvents\Bundle\Listener\Event\ListenerHandlerExceptionOccurred;
use RabbitEvents\Bundle\Listener\Event\ListenerHandling;
use RabbitEvents\Bundle\Listener\ListenerOptions;
use RabbitEvents\Bundle\Message\Envelope;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class Processor
{
    public function __construct(
        private Factory $handlerFactory,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Process a message by executing all registered handlers.
     *
     * @return bool
     */
    public function process(Envelope $message, ListenerOptions $options): bool
    {
        $handlers = $this->handlerFactory->create($message);

        foreach ($handlers as $handler) {
            try {
                $this->eventDispatcher->dispatch(new ListenerHandling($handler));

                $handler->handle();

                $this->eventDispatcher->dispatch(new ListenerHandled($handler));
            } catch (\Throwable $e) {
                $this->eventDispatcher->dispatch(new ListenerHandlerExceptionOccurred($handler, $e));

                $this->handleException($handler, $e, $options);
            }
        }

        return true;
    }

    protected function handleException(Handler $handler, \Throwable $exception, ListenerOptions $options): void
    {
        $this->logger->error($exception->getMessage(), [
            'exception' => $exception,
            'listener' => $handler->getListenerClass(),
            'event' => $handler->getMessage()->event,
        ]);

        try {
            // If retries are configured and we haven't exceeded them, release
            if ($options->maxTries > 0 && $handler->getMessage()->attempts() < $options->maxTries) {
                $handler->release($options->sleep);
                return;
            }

            $handler->fail($exception);

            $this->eventDispatcher->dispatch(new ListenerHandleFailed($handler, $exception));
        } catch (\Throwable $e) {
            $this->logger->error('Exception occurred while handling failure: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
        }
    }
}
