<?php

namespace RabbitEvents\Bundle\Tests\Message\Serializer\Json;

use Interop\Amqp\Impl\AmqpMessage;
use RabbitEvents\Bundle\Contract\Payload;
use RabbitEvents\Bundle\Message\Serializer\Json\Serializer;
use RabbitEvents\Bundle\Amqp\AmqpTransportMessage;
use RabbitEvents\Bundle\Tests\TestCase;

class SerializerTest extends TestCase
{
    public function testSerialize(): void
    {
        $serializer = new Serializer();
        $payload = $serializer->serialize(['foo' => 'bar']);

        self::assertInstanceOf(Payload::class, $payload);
        self::assertSame(json_encode(['foo' => 'bar']), $payload->serialize());
    }

    public function testDeserialize(): void
    {
        $serializer = new Serializer();

        $message = new AmqpMessage();
        $message->setBody(json_encode(['foo' => 'bar']));

        $payload = $serializer->deserialize(new AmqpTransportMessage($message));

        self::assertInstanceOf(Payload::class, $payload);
        self::assertSame(['foo' => 'bar'], $payload->value());
    }
}
