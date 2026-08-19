<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Message\Serializer;

use RabbitEvents\Bundle\Contract\Serializer;
use RabbitEvents\Bundle\Exception\UnsupportedContentTypeException;
use RabbitEvents\Bundle\Message\Serializer\Json\Serializer as JsonSerializer;

class Registry
{
    /**
     * @var array<string, Serializer>
     */
    private array $serializers = [];

    /**
     * @var Serializer
     */
    private Serializer $default;

    public function __construct(?Serializer $default = null)
    {
        $this->default = $default ?? new JsonSerializer();

        $this->register($this->default);
    }

    /**
     * Register a serializer.
     */
    public function register(Serializer $serializer): self
    {
        $this->serializers[(string) $serializer->contentType()] = $serializer;

        return $this;
    }

    /**
     * Register multiple serializers (used by DI container).
     *
     * @param iterable<Serializer> $serializers
     */
    public function registerMultiple(iterable $serializers): self
    {
        foreach ($serializers as $serializer) {
            $this->register($serializer);
        }

        return $this;
    }

    /**
     * Get a serializer by content type.
     *
     * @throws UnsupportedContentTypeException
     */
    public function get(string $contentType): Serializer
    {
        if (!isset($this->serializers[$contentType])) {
            throw new UnsupportedContentTypeException("Unsupported content type: $contentType");
        }

        return $this->serializers[$contentType];
    }

    /**
     * Get the default serializer.
     */
    public function getDefault(): Serializer
    {
        return $this->default;
    }

    /**
     * Find the serializer capable of serializing the payload.
     *
     * @param mixed $payload
     * @return Serializer
     */
    public function resolve(mixed $payload): Serializer
    {
        foreach ($this->serializers as $serializer) {
            if ($serializer->canSerialize($payload)) {
                return $serializer;
            }
        }

        return $this->default;
    }

    /**
     * @return array<string, Serializer>
     */
    public function all(): array
    {
        return $this->serializers;
    }
}
