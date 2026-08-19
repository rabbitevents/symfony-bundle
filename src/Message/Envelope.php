<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Message;

use RabbitEvents\Bundle\Contract\Serializer;
use RabbitEvents\Bundle\Contract\Payload;
use RabbitEvents\Bundle\Message\Serializer\Json\Serializer as JsonSerializer;
use RabbitEvents\Bundle\Contract\TransportMessage;

/**
 * @mixin TransportMessage
 */
class Envelope
{
    /**
     * @var TransportMessage|null
     */
    private ?TransportMessage $transportMessage = null;

    private ?Factory $factory = null;

    public function __construct(
        public readonly string $event,
        public readonly Payload $payload,
        private array $properties = []
    ) {
    }

    /**
     * @param TransportMessage $message
     * @param Serializer|null $serializer
     * @return self
     */
    public static function createFromTransportMessage(TransportMessage $message, ?Serializer $serializer = null): self
    {
        $serializer = $serializer ?? new JsonSerializer();

        return (new self(
            $message->getProperty('event') ?: (string) $message->getRoutingKey(),
            $serializer->deserialize($message),
            $message->getProperties()
        ))->setTransportMessage($message);
    }

    /**
     * @return TransportMessage
     */
    public function transportMessage(): TransportMessage
    {
        if (is_null($this->transportMessage)) {
            $factory = $this->factory ?? new Factory();
            $this->transportMessage = $factory->create(
                $this->event,
                $this->payload,
                $this->properties
            );
        }

        return $this->transportMessage;
    }

    public function __call(string $method, array $args): mixed
    {
        return $this->transportMessage()->$method(...$args);
    }

    public function attempts(): int
    {
        return (int) $this->getProperty('x-attempts', 0);
    }

    public function increaseAttempts(): self
    {
        $this->setProperty('x-attempts', $this->attempts() + 1);

        return $this;
    }

    /**
     * @param TransportMessage $transportMessage
     * @return self
     */
    public function setTransportMessage(TransportMessage $transportMessage): self
    {
        $this->transportMessage = $transportMessage;

        return $this;
    }

    /**
     * Set the message factory for creating transport messages.
     */
    public function setFactory(Factory $factory): self
    {
        $this->factory = $factory;

        return $this;
    }
}
