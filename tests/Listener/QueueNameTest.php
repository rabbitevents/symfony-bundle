<?php

namespace RabbitEvents\Bundle\Tests\Listener;

use RabbitEvents\Bundle\Listener\QueueName;
use RabbitEvents\Bundle\Tests\TestCase;

class QueueNameTest extends TestCase
{
    public function testResolveShortName()
    {
        $name = QueueName::resolve('app', ['order.created']);

        self::assertEquals('app:order.created', $name);
    }

    public function testResolveMultipleEvents()
    {
        $name = QueueName::resolve('app', ['order.created', 'order.updated']);

        self::assertEquals('app:order.created,order.updated', $name);
    }

    public function testResolveLongNameUsesHash()
    {
        $events = [];
        for ($i = 0; $i < 50; $i++) {
            $events[] = "very.long.event.name.number.$i";
        }

        $name = QueueName::resolve('app', $events);

        self::assertLessThanOrEqual(200, strlen($name));
        self::assertStringStartsWith('app:', $name);
    }
}
