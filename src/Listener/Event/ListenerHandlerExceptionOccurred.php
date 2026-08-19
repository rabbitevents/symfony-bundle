<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Listener\Event;

use RabbitEvents\Bundle\Listener\Handler\Handler;

class ListenerHandlerExceptionOccurred
{
    public function __construct(
        public readonly Handler $handler,
        public readonly \Throwable $exception
    ) {
    }
}
