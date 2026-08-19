<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle;

use RabbitEvents\Bundle\Contract\Connection;
use RabbitEvents\Bundle\Contract\Destination;
use RabbitEvents\Bundle\Contract\Producer;
use RabbitEvents\Bundle\Message\Serializer\Registry as SerializerRegistry;

class Context
{
    public function __construct(
        public readonly Connection $connection,
        public readonly SerializerRegistry $registry
    ) {
    }

    public function createTopic(): Destination
    {
        return $this->connection->createTopic();
    }

    public function createProducer(): Producer
    {
        return $this->connection->createProducer();
    }

    public function createConsumer(Destination $queue): Consumer
    {
        return new Consumer(
            $this->connection->createConsumer($queue),
            $this->registry
        );
    }

    public function createQueue(string $queueName, array $events, Destination $topic): Destination
    {
        return $this->connection->createQueue($queueName, $events, $topic);
    }
}
