<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Amqp;

use Interop\Amqp\Impl\AmqpMessage;
use RabbitEvents\Bundle\Contract\Payload;
use RabbitEvents\Bundle\Contract\TransportMessage;
use RabbitEvents\Bundle\Contract\TransportMessageFactory;

class AmqpMessageFactory implements TransportMessageFactory
{
    public function create(string $event, Payload $payload, array $properties = []): TransportMessage
    {
        $message = new AmqpMessage(
            $payload->serialize(),
            array_merge($properties, [
                'content_type' => (string) $payload->contentType(),
                'content_encoding' => 'UTF-8',
            ])
        );
        $message->setRoutingKey($event);
        $message->setProperty('event', $event);

        return new AmqpTransportMessage($message);
    }
}
