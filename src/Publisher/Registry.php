<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Publisher;

use RabbitEvents\Bundle\Publisher;

/**
 * Registry for the Publisher instance, managed by Symfony DI container.
 * This allows Publishable trait to access the publisher without users
 * needing to manually inject it into value object events.
 */
class Registry
{
    private static ?Publisher $publisher = null;
    
    /**
     * @var (callable(): Publisher)|null
     */
    private static $provider = null;

    /**
     * Set the publisher instance (called by Symfony DI during container compilation).
     */
    public static function set(Publisher $publisher): void
    {
        self::$publisher = $publisher;
    }
    
    /**
     * Set a provider to lazily retrieve the publisher.
     * 
     * @param callable(): Publisher $provider
     */
    public static function setProvider(callable $provider): void
    {
        self::$provider = $provider;
    }

    /**
     * Get the publisher instance.
     *
     * @throws \LogicException if publisher hasn't been set
     */
    public static function get(): Publisher
    {
        if (self::$publisher === null) {
            if (self::$provider !== null) {
                self::$publisher = (self::$provider)();
            }
            
            if (self::$publisher === null) {
                throw new \LogicException(
                    'Publisher not initialized. Make sure RabbitEventsBundle is registered.'
                );
            }
        }

        return self::$publisher;
    }

    /**
     * Reset the publisher (useful for testing).
     */
    public static function reset(): void
    {
        self::$publisher = null;
        self::$provider = null;
    }
}
