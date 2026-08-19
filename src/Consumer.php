<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle;

use RabbitEvents\Bundle\Contract\QueueConsumer;
use RabbitEvents\Bundle\Contract\Serializer;
use RabbitEvents\Bundle\Contract\TransportMessage;
use RabbitEvents\Bundle\Exception\ConnectionLostException;
use RabbitEvents\Bundle\Exception\UnsupportedContentTypeException;
use RabbitEvents\Bundle\Message\Envelope;
use RabbitEvents\Bundle\Message\Serializer\Registry as SerializerRegistry;

/**
 * @mixin QueueConsumer
 */
class Consumer
{
    public function __construct(private QueueConsumer $consumer, private SerializerRegistry $registry)
    {
    }

    public function __call(string $method, array $args)
    {
        return $this->consumer->$method(...$args);
    }

    /**
     * Receives a Message from the queue and returns Envelope object
     */
    public function nextMessage(int $timeout = 0): ?Envelope
    {
        if (!$transportMessage = $this->receiveMessage($timeout)) {
            return null;
        }

        // Set timestamp only if this message was not released before
        if (!$transportMessage->getTimestamp()) {
            $transportMessage->setTimestamp(time());
        }

        if (!$transportMessage->getProperty('event')) {
            $transportMessage->setProperty('event', $transportMessage->getRoutingKey());
        }

        try {
            $content_type = $transportMessage->getProperty('content_type');

            $serializer = $content_type ? $this->registry->get($content_type) : $this->registry->getDefault();

            return Envelope::createFromTransportMessage($transportMessage, $serializer)->increaseAttempts();
        } catch (UnsupportedContentTypeException $e) {
            $this->consumer->reject($transportMessage, false);
            throw $e;
        }
    }

    protected function receiveMessage(int $timeout = 0): ?TransportMessage
    {
        try {
            return $this->consumer->receive($timeout);
        } catch (\Throwable $exception) {
            throw new ConnectionLostException($exception);
        }
    }

    public function acknowledge(Envelope $message): void
    {
        $this->consumer->acknowledge($message->transportMessage());
    }
}
