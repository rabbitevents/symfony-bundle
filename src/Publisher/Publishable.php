<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Publisher;

/**
 * Trait for publishable events.
 * Provides a convenient publish() method that uses the Symfony-managed Publisher Registry.
 */
trait Publishable
{
    /**
     * Publish this event to RabbitMQ.
     */
    public function publish(): void
    {
        Registry::get()->publish($this);
    }
}
