<?php

declare(strict_types=1);

namespace App\Listener;

use Psr\Log\LoggerInterface;
use RabbitEvents\Bundle\Listener\Attribute\AsRabbitListener;

/**
 * Example of a listener with custom failure handling.
 *
 * If message processing fails after all retry attempts (configured via `--tries`),
 * the worker invokes the `failed(\Throwable $exception)` method on the listener
 * before marking the message as unprocessable.
 *
 * This allows you to perform cleanup, send alert notifications, or write to a dead-letter log.
 */
#[AsRabbitListener(event: 'payment.processed')]
class PaymentProcessedListener
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Main handler method.
     *
     * @param array{payment_id: string, amount: float, status: string} $payload
     */
    public function handle(array $payload): void
    {
        $this->logger->info('Processing payment event', ['payment_id' => $payload['payment_id']]);

        // If an exception is thrown and --tries > 0, the worker will retry with delay (--sleep).
    }

    /**
     * Called when all retry attempts have failed or unhandled exception occurred.
     */
    public function failed(\Throwable $exception): void
    {
        $this->logger->critical('Payment processing permanently failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Send alert, notify Slack/PagerDuty, or perform custom rollback
    }
}
