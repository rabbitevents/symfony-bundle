<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Tests\Support;

use Mockery as m;
use RabbitEvents\Bundle\Consumer;
use RabbitEvents\Bundle\Contract\Serializer;
use RabbitEvents\Bundle\Contract\ContentType;
use RabbitEvents\Bundle\Message\Envelope;
use RabbitEvents\Bundle\Message\Serializer\Registry as SerializerRegistry;
use RabbitEvents\Bundle\Listener\ListenerOptions;

/**
 * Trait for creating common test mocks and stubs.
 */
trait MocksCommonObjects
{
    /**
     * Create a mock Consumer instance.
     */
    protected function mockConsumer(array $methods = []): \Mockery\MockInterface
    {
        $consumer = m::mock(Consumer::class);

        foreach ($methods as $method => $return) {
            $consumer->shouldReceive($method)->andReturn($return);
        }

        return $consumer;
    }

    /**
     * Create a mock SerializerRegistry instance.
     */
    protected function mockSerializerRegistry(
        ?Serializer $defaultSerializer = null,
        array $serializers = []
    ): \Mockery\MockInterface {
        $registry = m::mock(SerializerRegistry::class);

        if ($defaultSerializer !== null) {
            $registry->shouldReceive('getDefault')->andReturn($defaultSerializer);
        }

        foreach ($serializers as $contentType => $serializer) {
            $registry->shouldReceive('get')->with($contentType)->andReturn($serializer);
        }

        return $registry;
    }

    /**
     * Create a mock Serializer instance.
     */
    protected function mockSerializer(string $contentType, array $methods = []): \Mockery\MockInterface
    {
        $type = m::mock(ContentType::class);
        $type->shouldReceive('__toString')->andReturn($contentType);
        $type->shouldReceive('getValue')->andReturn($contentType);

        $serializer = m::mock(Serializer::class);
        $serializer->shouldReceive('contentType')->andReturn($type);

        foreach ($methods as $method => $return) {
            $serializer->shouldReceive($method)->andReturn($return);
        }

        return $serializer;
    }

    /**
     * Create a mock Envelope instance.
     */
    protected function mockMessage(
        string $event = 'test.event',
        mixed $payload = null,
        int $attempts = 1
    ): \Mockery\MockInterface {
        $message = m::mock(Envelope::class);
        $message->shouldReceive('attempts')->andReturn($attempts);

        return $message;
    }

    /**
     * Create a ListenerOptions instance with defaults.
     */
    protected function createListenerOptions(array $overrides = []): ListenerOptions
    {
        $defaults = [
            'service' => 'test-app',
            'connection' => 'rabbitmq',
            'events' => ['rabbit.event'],
            'memory' => 128,
            'maxTries' => 0,
            'timeout' => 60,
            'sleep' => 5,
        ];

        $config = array_merge($defaults, $overrides);

        return new ListenerOptions(
            service: $config['service'],
            connection: $config['connection'],
            events: $config['events'],
            memory: $config['memory'],
            maxTries: $config['maxTries'],
            timeout: $config['timeout'],
            sleep: $config['sleep'],
        );
    }
}
