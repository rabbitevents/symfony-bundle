<?php

declare(strict_types=1);

namespace App\Event;

use RabbitEvents\Bundle\Publisher\ShouldPublish;

/**
 * Example of an event implementing ShouldPublish directly.
 *
 * This pattern is ideal when you prefer to inject `RabbitEvents\Bundle\Publisher`
 * via Symfony Dependency Injection into your service rather than calling `$event->publish()`.
 *
 * Usage with DI:
 *   class UserService {
 *       public function __construct(private \RabbitEvents\Bundle\Publisher $publisher) {}
 *       public function register(string $email): void {
 *           // ... save user ...
 *           $this->publisher->publish(new UserRegisteredEvent($userId, $email));
 *       }
 *   }
 */
class UserRegisteredEvent implements ShouldPublish
{
    public function __construct(
        public readonly int $userId,
        public readonly string $email,
        public readonly string $source = 'web'
    ) {
    }

    /**
     * Routing key for the event.
     */
    public function publishEventKey(): string
    {
        return 'user.registered';
    }

    /**
     * Payload published to the topic exchange.
     */
    public function toPublish(): array
    {
        return [
            'user_id' => $this->userId,
            'email' => $this->email,
            'source' => $this->source,
            'timestamp' => time(),
        ];
    }
}
