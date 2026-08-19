# RabbitEvents Symfony Bundle

[![Build Status](https://github.com/rabbitevents/symfony-bundle/actions/workflows/tests.yml/badge.svg)](https://github.com/rabbitevents/symfony-bundle/actions)
[![Latest Stable Version](https://poser.pugx.org/rabbitevents/symfony-bundle/v/stable)](https://packagist.org/packages/rabbitevents/symfony-bundle)
[![Total Downloads](https://poser.pugx.org/rabbitevents/symfony-bundle/downloads)](https://packagist.org/packages/rabbitevents/symfony-bundle)
[![License](https://poser.pugx.org/rabbitevents/symfony-bundle/license)](https://packagist.org/packages/rabbitevents/symfony-bundle)

**Publish and listen to events across microservices via RabbitMQ — for Symfony.**

This bundle provides inter-application event communication using RabbitMQ topic exchanges. Events published by any microservice (written in Symfony, Laravel using `nuwber/rabbitevents`, or any other language) are routed across services based on routing keys, with full support for wildcard routing.

---

## Features

- 🚀 **Seamless Interoperability:** Compatible with Laravel [`nuwber/rabbitevents`](https://github.com/nuwber/rabbitevents).
- 🏷️ **Modern PHP 8+ Attributes:** Register listeners via `#[AsRabbitListener]`, on whole classes or individual methods.
- ⚡ **Zero-Overhead Publisher:** Lazy connection initialization — RabbitMQ connections are only established when messages are published.
- 🔄 **Wildcard Routing:** Full AMQP topic routing key support (`*` and `#`).
- 📦 **Pluggable Serialization:** Built-in JSON serializer, optional Protobuf serializer, or custom serializers via Symfony DI tags.
- 🔁 **Resilience & Retries:** Built-in retry handling (`--tries`, `--sleep`), dead-letter support, and `failed()` listener callbacks.
- 🧪 **Testing Support:** `PublishableEventTesting` trait for asserting event publication without a live RabbitMQ broker.
- 🐘 **Modern PHP & Symfony:** Compatible with PHP 8.2+ and Symfony 6.4, 7.x, and 8.x.

---

## Table of Contents

- [Installation](#installation)
- [Quick Start](#quick-start)
- [Publishing Events](#publishing-events)
  - [1. Event Classes (`AbstractPublishableEvent`)](#1-event-classes-abstractpublishableevent)
  - [2. Publishing via Dependency Injection](#2-publishing-via-dependency-injection)
  - [3. Implementing `ShouldPublish` Directly](#3-implementing-shouldpublish-directly)
- [Listening for Events](#listening-for-events)
  - [Class-Level Listener Attribute](#class-level-listener-attribute)
  - [Method-Level Listener Attributes](#method-level-listener-attributes)
  - [Wildcard Listeners](#wildcard-listeners)
  - [Failure Handling (`failed()` callback)](#failure-handling-failed-callback)
- [Console Commands](#console-commands)
  - [`rabbitevents:listen`](#rabbiteventslisten)
  - [`rabbitevents:list`](#rabbiteventslist)
- [Configuration](#configuration)
- [Serialization](#serialization)
  - [JSON (Default)](#json-default)
  - [Protobuf Support](#protobuf-support)
  - [Custom Serializers](#custom-serializers)
- [Testing](#testing)
- [Examples](#examples)
- [License](#license)

---

## Installation

Install the bundle via Composer:

```bash
composer require rabbitevents/symfony-bundle
```

If you don't use Symfony Flex, enable the bundle in `config/bundles.php`:

```php
return [
    // ...
    RabbitEvents\Bundle\RabbitEventsBundle::class => ['all' => true],
];
```

---

## Quick Start

### 1. Configure Connection

Create `config/packages/rabbitevents.yaml`:

```yaml
rabbitevents:
    connection:
        host: '%env(RABBITMQ_HOST)%'
        port: '%env(int:RABBITMQ_PORT)%'
        user: '%env(RABBITMQ_USER)%'
        pass: '%env(RABBITMQ_PASSWORD)%'
        vhost: '/'
        exchange: 'events'
```

Add environment variables to your `.env`:

```env
RABBITMQ_HOST=127.0.0.1
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=guest
```

### 2. Create and Publish an Event

```php
namespace App\Event;

use RabbitEvents\Bundle\Publisher\AbstractPublishableEvent;

class OrderCreatedEvent extends AbstractPublishableEvent
{
    public function __construct(public readonly int $orderId)
    {
    }

    public function publishEventKey(): string
    {
        return 'order.created';
    }

    public function toPublish(): array
    {
        return [
            'order_id' => $this->orderId,
            'timestamp' => time(),
        ];
    }
}

// Publish from anywhere:
(new OrderCreatedEvent(12345))->publish();
```

### 3. Create a Listener

```php
namespace App\Listener;

use RabbitEvents\Bundle\Listener\Attribute\AsRabbitListener;

#[AsRabbitListener(event: 'order.created')]
class OrderCreatedListener
{
    public function handle(array $payload): void
    {
        // Handle payload: ['order_id' => 12345, 'timestamp' => ...]
        echo sprintf("Order #%d created!\n", $payload['order_id']);
    }
}
```

### 4. Start Listening

```bash
bin/console rabbitevents:listen order.created --service=my-service
```

---

## Publishing Events

### 1. Event Classes (`AbstractPublishableEvent`)

Extending `AbstractPublishableEvent` gives your event a static-style `$event->publish()` helper:

```php
namespace App\Event;

use RabbitEvents\Bundle\Publisher\AbstractPublishableEvent;

class UserRegisteredEvent extends AbstractPublishableEvent
{
    public function __construct(
        public readonly int $userId,
        public readonly string $email
    ) {
    }

    public function publishEventKey(): string
    {
        return 'user.registered';
    }

    public function toPublish(): array
    {
        return [
            'user_id' => $this->userId,
            'email' => $this->email,
        ];
    }
}

// Publish:
$event = new UserRegisteredEvent(1, 'user@example.com');
$event->publish();
```

### 2. Publishing via Dependency Injection

For clean architecture and easier unit testing, inject `RabbitEvents\Bundle\Publisher` directly into your services:

```php
namespace App\Service;

use App\Event\UserRegisteredEvent;
use RabbitEvents\Bundle\Publisher;

class UserService
{
    public function __construct(
        private readonly Publisher $publisher
    ) {
    }

    public function register(string $email): void
    {
        // ... save user ...
        $this->publisher->publish(new UserRegisteredEvent($user->getId(), $email));
    }
}
```

### 3. Implementing `ShouldPublish` Directly

If you prefer not to extend `AbstractPublishableEvent`, implement `ShouldPublish`:

```php
namespace App\Event;

use RabbitEvents\Bundle\Publisher\ShouldPublish;

class InvoiceGeneratedEvent implements ShouldPublish
{
    public function __construct(public readonly string $invoiceNumber) {}

    public function publishEventKey(): string
    {
        return 'invoice.generated';
    }

    public function toPublish(): array
    {
        return ['invoice_number' => $this->invoiceNumber];
    }
}
```

---

## Listening for Events

Listeners are automatically discovered and registered via the `#[AsRabbitListener]` attribute.

### Class-Level Listener Attribute

Place `#[AsRabbitListener]` on the class. The `handle()` method (or `__invoke()`) will be called when the event arrives:

```php
namespace App\Listener;

use Psr\Log\LoggerInterface;
use RabbitEvents\Bundle\Listener\Attribute\AsRabbitListener;

#[AsRabbitListener(event: 'order.created')]
class OrderCreatedListener
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function handle(array $payload): void
    {
        $this->logger->info('Order received', ['order_id' => $payload['order_id']]);
    }
}
```

### Method-Level Listener Attributes

You can group multiple event handlers in a single service class by annotating individual methods:

```php
namespace App\Listener;

use Psr\Log\LoggerInterface;
use RabbitEvents\Bundle\Listener\Attribute\AsRabbitListener;

class OrderLifecycleListener
{
    public function __construct(private readonly LoggerInterface $logger) {}

    #[AsRabbitListener(event: 'order.created')]
    public function onCreated(array $payload): void
    {
        $this->logger->info('Order created', $payload);
    }

    #[AsRabbitListener(event: 'order.updated')]
    public function onUpdated(array $payload): void
    {
        $this->logger->info('Order updated', $payload);
    }

    #[AsRabbitListener(event: 'order.cancelled')]
    public function onCancelled(array $payload): void
    {
        $this->logger->warning('Order cancelled', $payload);
    }
}
```

### Wildcard Listeners

RabbitEvents uses RabbitMQ topic exchanges, supporting wildcard matching in routing keys:

- `*` (asterisk) matches exactly **one** word: `order.*` matches `order.created`, `order.cancelled`.
- `#` (hash) matches **zero or more** words: `order.#` matches `order.created`, `order.item.added`.

```php
#[AsRabbitListener(event: 'order.*')]
class AllOrderEventsListener
{
    public function handle(array $payload): void
    {
        // Receives all order.* events
    }
}
```

### Failure Handling (`failed()` callback)

If an exception occurs during processing and retry attempts are exhausted, the worker calls the `failed(\Throwable $e)` method on your listener:

```php
#[AsRabbitListener(event: 'payment.processed')]
class PaymentListener
{
    public function handle(array $payload): void
    {
        // May throw an exception if payment gateway is down
    }

    public function failed(\Throwable $exception): void
    {
        // Cleanup, send alerts to Slack / Sentry, etc.
    }
}
```

---

## Console Commands

### `rabbitevents:listen`

Start a background worker to consume events from RabbitMQ:

```bash
# Listen for specific events:
bin/console rabbitevents:listen order.created order.updated --service=my-service

# Listen with wildcards:
bin/console rabbitevents:listen "order.*" --service=my-service
```

#### Available Options

| Option | Description | Default |
|---|---|---|
| `--service`, `-s` | Service name used to isolate queue names per service | `app` |
| `--memory` | Memory limit in MB before worker gracefully restarts | `128` |
| `--timeout` | Max processing timeout in seconds per message | `60` |
| `--tries` | Max retry attempts for failed messages | `0` (no retries) |
| `--sleep` | Sleep delay in seconds between retries | `1` |

### `rabbitevents:list`

List all discovered RabbitEvents listeners and their routing keys:

```bash
bin/console rabbitevents:list
```

```
+-----------------+-------------------------------+
| Event           | Listeners                     |
+-----------------+-------------------------------+
| order.created   | App\Listener\OrderCreated...  |
| order.cancelled | App\Listener\OrderCreated...  |
| user.*          | App\Listener\UserWildcard...  |
+-----------------+-------------------------------+
```

---

## Configuration

Full options reference for `config/packages/rabbitevents.yaml`:

```yaml
rabbitevents:
    connection:
        host: '%env(RABBITMQ_HOST)%'          # Default: 127.0.0.1
        port: '%env(int:RABBITMQ_PORT)%'      # Default: 5672
        user: '%env(RABBITMQ_USER)%'          # Default: guest
        pass: '%env(RABBITMQ_PASSWORD)%'      # Default: guest
        vhost: '/'                             # Default: /
        exchange: 'events'                     # Topic exchange name (default: events)
        read_timeout: 3.0                      # Socket read timeout (seconds)
        write_timeout: 3.0                     # Socket write timeout (seconds)
        connection_timeout: 3.0                # Connection timeout (seconds)
        heartbeat: 0                           # Heartbeat (seconds, 0 = disabled)
        lazy: true                             # Defer connection until first publish/consume
        qos:
            prefetch_count: 1                  # AMQP prefetch count
            prefetch_size: 0
            global: false
        ssl:
            enabled: false
            verify_peer: true
            cafile: null
            local_cert: null
            local_key: null
            passphrase: ''

    # Default serializer class (implements Serializer interface)
    default_serializer: RabbitEvents\Bundle\Message\Serializer\Json\Serializer

    logging:
        enabled: false
        level: 'info'
```

---

## Serialization

### JSON (Default)

The default serializer encodes arrays and objects as JSON and decodes them to associative arrays in your listener's `handle(array $payload)` method.

### Protobuf Support

If `google/protobuf` is installed in your project, the `Protobuf` serializer is automatically registered:

```bash
composer require google/protobuf
```

### Custom Serializers

To register a custom serializer, implement `RabbitEvents\Bundle\Contract\Serializer` and tag it:

```yaml
# config/services.yaml
services:
    App\Serializer\XmlSerializer:
        tags:
            - { name: 'rabbitevents.serializer' }
```

---

## Testing

Use the `PublishableEventTesting` trait in PHPUnit tests to fake event publication without connecting to RabbitMQ:

```php
namespace App\Tests;

use App\Event\OrderCreatedEvent;
use App\Service\OrderService;
use PHPUnit\Framework\TestCase;
use RabbitEvents\Bundle\Publisher\PublishableEventTesting;

class OrderServiceTest extends TestCase
{
    use PublishableEventTesting;

    protected function setUp(): void
    {
        parent::setUp();
        self::fake(); // Replaces the real Publisher with a testing spy
    }

    public function testOrderCreatesEvent(): void
    {
        $service = new OrderService();
        $service->createOrder(orderId: 100);

        OrderCreatedEvent::assertPublished('order.created', [
            'order_id' => 100,
        ]);
    }

    public function testEventNotPublishedOnFailure(): void
    {
        $service = new OrderService();
        // ... action that should not publish ...

        OrderCreatedEvent::assertNotPublished();
    }
}
```

---

## Examples

Check the [`examples/`](./examples) directory for complete, ready-to-use examples:

- [**`OrderCreatedEvent.php`**](./examples/Events/OrderCreatedEvent.php) — Standard publishable event.
- [**`UserRegisteredEvent.php`**](./examples/Events/UserRegisteredEvent.php) — Event using `ShouldPublish` and DI injection.
- [**`OrderCreatedListener.php`**](./examples/Listeners/OrderCreatedListener.php) — Class-level `#[AsRabbitListener]`.
- [**`OrderLifecycleListener.php`**](./examples/Listeners/OrderLifecycleListener.php) — Multi-method `#[AsRabbitListener]`.
- [**`UserWildcardListener.php`**](./examples/Listeners/UserWildcardListener.php) — Wildcard `user.*` pattern matching.
- [**`PaymentProcessedListener.php`**](./examples/Listeners/PaymentProcessedListener.php) — Custom error and failure handling (`failed()`).

---

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).
