<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Message\Serializer\Json;

use RabbitEvents\Bundle\Contract\ContentType as ContentTypeContract;

class ContentType implements ContentTypeContract
{
    public const VALUE = 'application/json';

    public function getValue(): string
    {
        return self::VALUE;
    }

    public function __toString(): string
    {
        return $this->getValue();
    }
}
