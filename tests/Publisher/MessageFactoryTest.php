<?php

namespace RabbitEvents\Bundle\Tests\Publisher;

use RabbitEvents\Bundle\Publisher\MessageFactory;
use RabbitEvents\Bundle\Publisher\ShouldPublish;
use RabbitEvents\Bundle\Message\Serializer\Json\Payload;
use RabbitEvents\Bundle\Message\Serializer\Json\ContentType;
use RabbitEvents\Bundle\Message\Serializer\Registry;
use RabbitEvents\Bundle\Contract\Serialize;
use RabbitEvents\Bundle\Contract\Payload as PayloadContract;
use RabbitEvents\Bundle\Tests\TestCase;

class MessageFactoryTest extends TestCase
{
    public function testMakeFromArray(): void
    {
        $payload = new Payload([]);

        $serializer = \Mockery::mock(\RabbitEvents\Bundle\Contract\Serializer::class);
        $serializer->shouldReceive('serialize')->andReturn($payload);
        $serializer->shouldReceive('contentType')->andReturn(new ContentType());

        $registry = \Mockery::mock(Registry::class);
        $registry->shouldReceive('resolve')->andReturn($serializer);

        $factory = new MessageFactory($registry);
        $message = $factory->create(new TestEvent());

        self::assertEquals('some.event', $message->event);
        self::assertInstanceOf(PayloadContract::class, $message->payload);
    }

    public function testMakeFromPayloadObject(): void
    {
        $payload = new Payload(['pay' => 'load']);

        $event = new TestEvent();
        $event->toPublishValue = $payload;
    
        $serializer = \Mockery::mock(\RabbitEvents\Bundle\Contract\Serializer::class);
        $serializer->shouldReceive('serialize')->with($payload)->andReturn($payload);
        $serializer->shouldReceive('contentType')->andReturn(new ContentType());

        $registry = \Mockery::mock(Registry::class);
        $registry->shouldReceive('resolve')->with($payload)->andReturn($serializer);

        $factory = new MessageFactory($registry);
        $message = $factory->create($event);

        self::assertSame($payload, $message->payload);
    }
}

class TestEvent implements ShouldPublish
{
    public $toPublishValue = ['pay' => 'load'];

    public function publishEventKey(): string
    {
        return 'some.event';
    }

    public function toPublish(): mixed
    {
        return $this->toPublishValue;
    }
}
