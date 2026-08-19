<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Tests\Listener\Handler;

use RabbitEvents\Bundle\Contract\Payload;
use RabbitEvents\Bundle\Listener\Handler\Handler;
use RabbitEvents\Bundle\Message\Envelope;
use RabbitEvents\Bundle\Tests\TestCase;

class HandlerTest extends TestCase
{
    public function testHandleWithClosure(): void
    {
        $called = false;
        $closure = function (string $event, mixed $payload) use (&$called) {
            $called = true;
            return [$event, $payload];
        };

        $payloadMock = \Mockery::mock(Payload::class);
        $payloadMock->shouldReceive('value')->andReturn(['key' => 'val']);

        $message = new Envelope('order.created', $payloadMock);

        $handler = new Handler('Closure', $closure, $message);
        $result = $handler->handle();

        self::assertTrue($called);
        self::assertEquals(['order.created', ['key' => 'val']], $result);
    }

    public function testHandleWithServiceObject(): void
    {
        $service = new class {
            public bool $handled = false;
            public function handle(mixed $payload): mixed
            {
                $this->handled = true;
                return $payload;
            }
        };

        $payloadMock = \Mockery::mock(Payload::class);
        $payloadMock->shouldReceive('value')->andReturn(['order_id' => 123]);

        $message = new Envelope('order.created', $payloadMock);

        $handler = new Handler(get_class($service), $service, $message);
        $result = $handler->handle();

        self::assertTrue($service->handled);
        self::assertEquals(['order_id' => 123], $result);
    }

    public function testHandleWithArrayCallable(): void
    {
        $service = new class {
            public bool $customCalled = false;
            public function customMethod(mixed $payload): mixed
            {
                $this->customCalled = true;
                return 'custom_result';
            }
        };

        $payloadMock = \Mockery::mock(Payload::class);
        $payloadMock->shouldReceive('value')->andReturn(['order_id' => 456]);

        $message = new Envelope('order.cancelled', $payloadMock);

        $handler = new Handler(get_class($service), [$service, 'customMethod'], $message);
        $result = $handler->handle();

        self::assertTrue($service->customCalled);
        self::assertSame('custom_result', $result);
    }
}
