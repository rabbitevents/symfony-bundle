<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Listener\Attribute;

/**
 * Attribute to mark a class or method as a RabbitEvents listener.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class AsRabbitListener
{
    /**
     * @param string $event Event routing key to listen for
     */
    public function __construct(public readonly string $event)
    {
    }
}
