<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Message\Serializer\Protobuf;

use Google\Protobuf\Internal\Message;
use RabbitEvents\Bundle\Contract\ContentType as ContentTypeContract;
use RabbitEvents\Bundle\Contract\Payload as PayloadContract;
use RabbitEvents\Bundle\Contract\Serializer as SerializerContract;
use RabbitEvents\Bundle\Contract\TransportMessage;

class Serializer implements SerializerContract
{
    public function serialize(mixed $payload): PayloadContract
    {
        if (!$payload instanceof Message) {
            throw new \InvalidArgumentException('Value must be instance of Google\Protobuf\Internal\Message');
        }

        return new Payload($payload);
    }

    public function deserialize(TransportMessage $message): PayloadContract
    {
        $payload = $message->getBody();
        $type = $message->getProperty('type');

        if (class_exists($type) && is_subclass_of($type, Message::class)) {
            $object = new $type();
            $object->mergeFromString($payload);

            return new Payload($object);
        }

        throw new \RuntimeException('Generic deserialization for Protobuf is not supported without "type" property with valid class name.');
    }

    public function contentType(): ContentTypeContract
    {
        return new ContentType();
    }

    public function canSerialize(mixed $payload): bool
    {
        return $payload instanceof Message;
    }
}
