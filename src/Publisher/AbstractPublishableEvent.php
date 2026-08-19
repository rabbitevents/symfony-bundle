<?php

declare(strict_types=1);

namespace RabbitEvents\Bundle\Publisher;

/**
 * Abstract base class for publishable events.
 * Combines the ShouldPublish interface with the Publishable trait.
 * 
 * Usage:
 * class OrderCreated extends AbstractPublishableEvent {
 *     public function __construct(public int $orderId) {}
 *     public function publishEventKey(): string { return 'order.created'; }
 *     public function toPublish(): mixed { return ['order_id' => $this->orderId]; }
 * }
 */
abstract class AbstractPublishableEvent implements ShouldPublish
{
    use Publishable;
}
