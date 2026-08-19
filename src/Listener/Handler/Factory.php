<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Listener\Handler;

use RabbitEvents\Bundle\Contract\Transport;
use RabbitEvents\Bundle\Listener\Registry;
use RabbitEvents\Bundle\Message\Envelope;

class Factory
{
    public function __construct(
        private Registry $listenerRegistry,
        private ?Transport $releaser = null
    ) {
    }

    /**
     * @param Envelope $message
     * @return Handler[]
     */
    public function create(Envelope $message): array
    {
        $handlers = [];

        foreach ($this->listenerRegistry->getListeners($message->event) as $listener) {
            $listenerClass = $this->resolveListenerClass($listener);

            $handler = new Handler(
                $listenerClass,
                $listener,
                $message,
                $this->releaser
            );

            // Set up failed callback if the listener class has a failed() method
            if (class_exists($listenerClass) && method_exists($listenerClass, 'failed')) {
                $handler->setFailedCallback(function (\Throwable $e) use ($listener, $listenerClass) {
                    // Use existing instance when available (DI-injected service)
                    if (is_object($listener) && !$listener instanceof \Closure) {
                        $listener->failed($e);
                    } else {
                        (new $listenerClass())->failed($e);
                    }
                });
            }

            $handlers[] = $handler;
        }

        return $handlers;
    }

    private function resolveListenerClass(mixed $listener): string
    {
        if (is_string($listener)) {
            return $listener;
        }

        if (is_array($listener)) {
            $target = $listener[0] ?? null;
            if (is_string($target)) {
                return $target;
            }
            if (is_object($target)) {
                return get_class($target);
            }
        }

        if (is_object($listener) && !$listener instanceof \Closure) {
            return get_class($listener);
        }

        return 'Closure';
    }
}
