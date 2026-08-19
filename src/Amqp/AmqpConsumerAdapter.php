<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Amqp;

use Interop\Amqp\AmqpConsumer;
use RabbitEvents\Bundle\Contract\QueueConsumer;
use RabbitEvents\Bundle\Contract\TransportMessage;

class AmqpConsumerAdapter implements QueueConsumer
{
    public function __construct(private AmqpConsumer $consumer)
    {
    }

    public function receive(int $timeout = 0): ?TransportMessage
    {
        if ($message = $this->consumer->receive($timeout)) {
            return new AmqpTransportMessage($message);
        }

        return null;
    }

    public function acknowledge(TransportMessage $message): void
    {
        $this->consumer->acknowledge($message->getOrigin());
    }

    public function reject(TransportMessage $message, bool $requeue = false): void
    {
        $this->consumer->reject($message->getOrigin(), $requeue);
    }

    public function __call(string $method, array $args)
    {
        return $this->consumer->$method(...$args);
    }
}
