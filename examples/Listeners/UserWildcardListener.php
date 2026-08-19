<?php

declare(strict_types=1);

namespace App\Listener;

use Psr\Log\LoggerInterface;
use RabbitEvents\Bundle\Listener\Attribute\AsRabbitListener;

/**
 * Example of a wildcard listener that consumes any user event.
 *
 * Routing key patterns:
 * - `user.*` matches `user.registered`, `user.updated`, `user.deleted` (single word wildcard).
 * - `user.#` matches `user.created`, `user.profile.updated`, etc. (multi-word wildcard).
 *
 * When run with:
 *   bin/console rabbitevents:listen "user.*" --service=audit-service
 */
#[AsRabbitListener(event: 'user.*')]
class UserWildcardListener
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Handles any event matching the 'user.*' pattern.
     *
     * @param array<string, mixed> $payload
     */
    public function handle(array $payload): void
    {
        $this->logger->info('Audit log: User event received', [
            'payload' => $payload,
        ]);
    }
}
