<?php

declare(strict_types=1);

namespace App\Listener;

use Psr\Log\LoggerInterface;
use RabbitEvents\Bundle\Listener\Attribute\AsRabbitListener;

/**
 * Example of grouping multiple event handlers in a single service class
 * using method-level #[AsRabbitListener] attributes.
 *
 * This reduces boilerplate when multiple related events share dependencies.
 */
class OrderLifecycleListener
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Handles 'order.created' event.
     */
    #[AsRabbitListener(event: 'order.created')]
    public function onOrderCreated(array $payload): void
    {
        $this->logger->info('Order created handler called', ['order_id' => $payload['order_id'] ?? null]);
    }

    /**
     * Handles 'order.updated' event.
     */
    #[AsRabbitListener(event: 'order.updated')]
    public function onOrderUpdated(array $payload): void
    {
        $this->logger->info('Order updated handler called', ['order_id' => $payload['order_id'] ?? null]);
    }

    /**
     * Handles 'order.cancelled' event.
     */
    #[AsRabbitListener(event: 'order.cancelled')]
    public function onOrderCancelled(array $payload): void
    {
        $this->logger->warning('Order cancelled handler called', [
            'order_id' => $payload['order_id'] ?? null,
            'reason' => $payload['reason'] ?? 'N/A',
        ]);
    }
}
