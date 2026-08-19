<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Contract;

interface TransportMessageFactory
{
    public function create(string $event, Payload $payload, array $properties = []): TransportMessage;
}
