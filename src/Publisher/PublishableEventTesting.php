<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Publisher;

use Mockery;
use RabbitEvents\Bundle\Publisher;

/**
 * Testing helper trait for publishable events.
 * Uses Mockery for mocking the Publisher via Registry.
 */
trait PublishableEventTesting
{
    /**
     * Replace the real Publisher with a Mockery spy for testing.
     */
    public static function fake(): void
    {
        Registry::set(Mockery::spy(Publisher::class));
    }

    /**
     * Assert that an event was published.
     */
    public static function assertPublished(string $event, ?array $payload = null): void
    {
        Registry::get()
            ->shouldHaveReceived()
            ->publish(Mockery::on(static function (ShouldPublish $object) use ($event, $payload) {
                return $object instanceof static
                    && $object->publishEventKey() === $event
                    && (is_null($payload) || $object->toPublish() === $payload);
            }))
            ->once();
    }

    /**
     * Assert that an event was NOT published.
     */
    public static function assertNotPublished(): void
    {
        Registry::get()
            ->shouldNotHaveReceived()
            ->publish(Mockery::type(static::class));
    }
}
