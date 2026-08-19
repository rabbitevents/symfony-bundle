<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Tests\Support\Stubs;

use RabbitEvents\Bundle\Contract\TransportMessage;

class TransportMessageStub implements TransportMessage
{
    public function __construct(
        private string $body = '',
        private array $properties = [],
        private ?string $routingKey = null
    ) {
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getRoutingKey(): ?string
    {
        return $this->routingKey;
    }

    public function getProperty(string $name, mixed $default = null): mixed
    {
        return $this->properties[$name] ?? $default;
    }

    public function setProperty(string $name, mixed $value): void
    {
        $this->properties[$name] = $value;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getTimestamp(): ?int
    {
        return $this->properties['timestamp'] ?? null;
    }

    public function setTimestamp(int $timestamp): void
    {
        $this->properties['timestamp'] = $timestamp;
    }

    public function getOrigin(): mixed
    {
        return null; // Mock origin
    }
}
