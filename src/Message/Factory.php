<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Message;

use RabbitEvents\Bundle\Amqp\AmqpMessageFactory;
use RabbitEvents\Bundle\Contract\Payload;
use RabbitEvents\Bundle\Contract\TransportMessage;
use RabbitEvents\Bundle\Contract\TransportMessageFactory;

/**
 * Factory for creating transport messages.
 *
 * Registered as a DI service and injected where needed.
 * Falls back to AmqpMessageFactory if no factory is explicitly provided.
 */
class Factory
{
    public function __construct(private ?TransportMessageFactory $factory = null)
    {
    }

    public function create(string $event, Payload $payload, array $properties = []): TransportMessage
    {
        return $this->getFactory()->create($event, $payload, $properties);
    }

    private function getFactory(): TransportMessageFactory
    {
        if ($this->factory === null) {
            $this->factory = new AmqpMessageFactory();
        }

        return $this->factory;
    }
}
