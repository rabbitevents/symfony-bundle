<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Message\Serializer\Protobuf;

use Google\Protobuf\Internal\Message;
use RabbitEvents\Bundle\Contract\ContentType as ContentTypeContract;
use RabbitEvents\Bundle\Contract\Payload as PayloadContract;
use RabbitEvents\Bundle\Message\Serializer\Protobuf\ContentType as ProtobufContentType;

class Payload implements PayloadContract
{
    public function __construct(private Message $message)
    {
    }

    public function value(): Message
    {
        return $this->message;
    }

    public function serialize(): string
    {
        return $this->message->serializeToString();
    }

    public function contentType(): ContentTypeContract
    {
        return new ProtobufContentType();
    }
}
