<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Amqp;

use Interop\Amqp\AmqpProducer;
use RabbitEvents\Bundle\Contract\Destination;
use RabbitEvents\Bundle\Contract\Producer;
use RabbitEvents\Bundle\Contract\TransportMessage;

class AmqpProducerAdapter implements Producer
{
    public function __construct(private AmqpProducer $producer)
    {
    }

    public function send(Destination $destination, TransportMessage $message): void
    {
        $this->producer->send($destination->getOrigin(), $message->getOrigin());
    }

    public function setDeliveryDelay(?int $deliveryDelay = null): void
    {
        $this->producer->setDeliveryDelay($deliveryDelay);
    }

    public function __call(string $method, array $args)
    {
        return $this->producer->$method(...$args);
    }
}
