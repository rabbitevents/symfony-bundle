<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Publisher;

use RabbitEvents\Bundle\Message\Envelope;
use RabbitEvents\Bundle\Message\Serializer\Registry as SerializerRegistry;

class MessageFactory
{
    public function __construct(private SerializerRegistry $registry)
    {
    }

    public function create(ShouldPublish $event): Envelope
    {
        $serializer = $this->registry->resolve($event->toPublish());

        $payload = $serializer->serialize($event->toPublish());

        return new Envelope(
            $event->publishEventKey(),
            $payload,
            [
                'content_type' => (string) $payload->contentType(),
                'event' => $event->publishEventKey(),
                'timestamp' => time(),
            ]
        );
    }
}
