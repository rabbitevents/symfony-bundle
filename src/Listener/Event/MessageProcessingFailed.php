<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Listener\Event;

use RabbitEvents\Bundle\Message\Envelope;

class MessageProcessingFailed
{
    public function __construct(
        public readonly Envelope $message,
        public readonly \Throwable $exception
    ) {
    }
}
