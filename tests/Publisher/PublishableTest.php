<?php

namespace RabbitEvents\Bundle\Tests\Publisher;

use RabbitEvents\Bundle\Publisher\AbstractPublishableEvent;
use RabbitEvents\Bundle\Publisher;
use RabbitEvents\Bundle\Publisher\Registry;
use Mockery as m;
use RabbitEvents\Bundle\Tests\TestCase;

class PublishableTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Registry::reset();
    }

    protected function tearDown(): void
    {
        Registry::reset();
        parent::tearDown();
    }

    public function testPublish(): void
    {
        $publisher = m::mock(Publisher::class);
        $publisher->shouldReceive('publish')->once();

        Registry::set($publisher);

        $event = new class extends AbstractPublishableEvent {
            public function publishEventKey(): string
            {
                return 'test.event';
            }

            public function toPublish(): mixed
            {
                return ['test' => 'data'];
            }
        };

        $event->publish();

        self::assertTrue(true); // Mockery assertions are on shouldReceive
    }

    public function testPublishThrowsWhenRegistryNotInitialized(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Publisher not initialized');

        $event = new class extends AbstractPublishableEvent {
            public function publishEventKey(): string
            {
                return 'test.event';
            }

            public function toPublish(): mixed
            {
                return [];
            }
        };

        $event->publish();
    }
}
