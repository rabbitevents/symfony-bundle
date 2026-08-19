<?php

declare(strict_types=1);

namespace App\Event;

use RabbitEvents\Bundle\Publisher\AbstractPublishableEvent;

/**
 * Example of a standard publishable event.
 *
 * Extending AbstractPublishableEvent provides the `publish()` helper method
 * which publishes the event via the global Publisher registry.
 *
 * Usage:
 *   $event = new OrderCreatedEvent(orderId: 12345, customerId: 99, totalAmount: 49.99);
 *   $event->publish();
 */
class OrderCreatedEvent extends AbstractPublishableEvent
{
    public function __construct(
        public readonly int $orderId,
        public readonly int $customerId,
        public readonly float $totalAmount,
        public readonly string $currency = 'USD'
    ) {
    }

    /**
     * Routing key for the event.
     * Listeners bind to this routing key (or wildcard patterns like `order.*`).
     */
    public function publishEventKey(): string
    {
        return 'order.created';
    }

    /**
     * Event payload serialized to JSON by default.
     * Must return an array or object serializable by the configured Serializer.
     */
    public function toPublish(): array
    {
        return [
            'order_id' => $this->orderId,
            'customer_id' => $this->customerId,
            'total_amount' => $this->totalAmount,
            'currency' => $this->currency,
            'created_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];
    }
}
