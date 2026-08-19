<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Message\Serializer\Protobuf;

use RabbitEvents\Bundle\Contract\ContentType as ContentTypeContract;

class ContentType implements ContentTypeContract
{
    public function __toString(): string
    {
        return 'application/x-protobuf';
    }

    public function getValue(): string
    {
        return 'application/x-protobuf';
    }
}
