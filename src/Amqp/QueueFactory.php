<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Amqp;

use Interop\Amqp\AmqpDestination;
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\AmqpContext;

class QueueFactory
{
    public function __construct(private readonly AmqpContext $context, private readonly AmqpConnection $connection)
    {
    }

    /**
     * @param string $queueName
     * @return AmqpQueue
     */
    public function createAndDeclare(string $queueName): AmqpQueue
    {
        $queue = $this->context->createQueue($queueName);

        if ($this->connection->getConfig('durable', true)) {
            $queue->addFlag(AmqpDestination::FLAG_DURABLE);
        }

        $this->context->declareQueue($queue);

        return $queue;
    }
}
