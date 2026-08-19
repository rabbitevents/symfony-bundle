<?php

namespace RabbitEvents\Bundle\Tests\Message;

use Interop\Amqp\AmqpMessage;
use RabbitEvents\Bundle\Contract\TransportMessage;
use RabbitEvents\Bundle\Message\Envelope;
use RabbitEvents\Bundle\Message\Serializer\Json\Payload;
use RabbitEvents\Bundle\Tests\TestCase;
use RabbitEvents\Bundle\Tests\Support\Stubs\TransportMessageStub;

class EnvelopeTest extends TestCase
{
    public function testAmqpMessage()
    {
        $message = new Envelope('item.created', new Payload([]));

        self::assertInstanceOf(TransportMessage::class, $message->transportMessage());
        self::assertInstanceOf(AmqpMessage::class, $message->transportMessage()->getOrigin());

        $amqpMessage = new \Interop\Amqp\Impl\AmqpMessage();
        $transportMessage = new \RabbitEvents\Bundle\Amqp\AmqpTransportMessage($amqpMessage);

        self::assertNotSame($transportMessage, $message->transportMessage());

        $message->setTransportMessage($transportMessage);

        self::assertSame($transportMessage, $message->transportMessage());
        self::assertSame($amqpMessage, $message->transportMessage()->getOrigin());
    }

    public function testIncreaseAttempts()
    {
        $message = new Envelope('item.created', new Payload([]));

        $transportMessageStub = new TransportMessageStub();

        $message->setTransportMessage($transportMessageStub);

        self::assertEquals(0, $message->attempts());

        $message->increaseAttempts();

        self::assertEquals(1, $message->attempts());
    }

    public function testCreateFromTransportMessage()
    {
        $payload = ['pay' => 'load'];

        $amqpMessage = new \Interop\Amqp\Impl\AmqpMessage();
        $amqpMessage->setRoutingKey($event = 'item.created');
        $amqpMessage->setBody(json_encode($payload));

        $transportMessage = new \RabbitEvents\Bundle\Amqp\AmqpTransportMessage($amqpMessage);

        $message = Envelope::createFromTransportMessage($transportMessage);

        self::assertEquals($event, $message->event);
        self::assertEquals($payload, $message->payload->value());
    }
}
