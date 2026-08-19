<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Message\Serializer\Json;

use RabbitEvents\Bundle\Contract\ContentType as ContentTypeContract;
use RabbitEvents\Bundle\Contract\Payload as PayloadContract;
use RabbitEvents\Bundle\Contract\Serializer as SerializerContract;
use RabbitEvents\Bundle\Contract\TransportMessage;

class Serializer implements SerializerContract
{
    public function serialize(mixed $payload): PayloadContract
    {
        return new Payload($payload);
    }

    public function deserialize(TransportMessage $message): PayloadContract
    {
        return new Payload($message->getBody());
    }

    public function contentType(): ContentTypeContract
    {
        return new ContentType();
    }

    public function canSerialize(mixed $payload): bool
    {
        return is_array($payload)
            || $payload instanceof \JsonSerializable
            || is_scalar($payload);
    }
}
