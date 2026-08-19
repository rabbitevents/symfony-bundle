<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Listener;

class ListenerOptions
{
    public function __construct(
        public string $service = '',
        public string $connection = 'default',
        public array $events = [],
        public int $memory = 128,
        public int $timeout = 60,
        public int $maxTries = 0,
        public int $sleep = 5,
    ) {
    }
}
