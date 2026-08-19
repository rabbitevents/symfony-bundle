<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Contract;

interface Connection
{
    public function createProducer(): Producer;

    public function createConsumer(Destination $queue): QueueConsumer;

    public function createTopic(): Destination;

    public function createQueue(string $queueName, array $events, Destination $topic): Destination;
}
