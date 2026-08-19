<?php

declare(strict_types=1);

namespace App\Listener;

use Psr\Log\LoggerInterface;
use RabbitEvents\Bundle\Listener\Attribute\AsRabbitListener;

/**
 * Example of a dedicated event listener class using a class-level attribute.
 *
 * Key benefits:
 * - Discovered automatically by Symfony DI during container build.
 * - Constructor dependencies (logger, repositories, mailer, etc.) are fully autowired.
 * - The handle() method receives the deserialized payload (array by default).
 */
#[AsRabbitListener(event: 'order.created')]
class OrderCreatedListener
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Handles the 'order.created' event.
     *
     * @param array{order_id: int, customer_id: int, total_amount: float, currency: string} $payload
     */
    public function handle(array $payload): void
    {
        $this->logger->info('Processing new order', [
            'order_id' => $payload['order_id'] ?? null,
            'customer_id' => $payload['customer_id'] ?? null,
            'total' => $payload['total_amount'] ?? 0.0,
        ]);

        // Perform business logic (e.g. reserve inventory, send notification, etc.)
    }
}
