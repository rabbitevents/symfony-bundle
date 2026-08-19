<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Message\Serializer\Json;

use RabbitEvents\Bundle\Contract\ContentType as ContentTypeContract;
use RabbitEvents\Bundle\Contract\Payload as PayloadContract;
use RabbitEvents\Bundle\Message\Serializer\Json\ContentType as JsonContentType;

class Payload implements PayloadContract
{
    protected mixed $value;

    public function __construct(array|\JsonSerializable|string $value)
    {
        $this->value = is_string($value) ? json_decode($value, true, 512, JSON_THROW_ON_ERROR) : $value;
    }

    public function value(): mixed
    {
        return $this->value;
    }

    public function serialize(): string
    {
        return json_encode(
            $this->value instanceof \JsonSerializable ? $this->value->jsonSerialize() : $this->value,
            JSON_THROW_ON_ERROR
        );
    }

    public function contentType(): ContentTypeContract
    {
        return new JsonContentType();
    }
}
