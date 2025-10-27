## Laravel RabbitMQ

A lightweight, fluent RabbitMQ integration for Laravel. Manage exchanges, queues, and bindings; publish and consume messages; generate consumer classes; and configure multiple connections.

Supports Laravel 10/11/12 and PHP 8.1+ (uses `php-amqplib/php-amqplib` v3).

### Features
- Manage exchanges, queues, and bindings via a fluent service
- Publish JSON messages easily
- Consume via Artisan or programmatically
- Multiple connections out of the box
- Artisan generator for consumer classes with publishable stub
- Configuration publishing

---

## Installation

```bash
composer require batatahub-tech/laravel-rabbitmq
```

The service provider is auto-discovered. No manual registration needed.

### Publish configuration and stub (optional)

```bash
# Publish config/rabbitmq.php
php artisan vendor:publish --tag=config --provider="BatataHub\\RabbitMQ\\Providers\\RabbitMQServiceProvider"

# Publish the consumer stub to stubs/rabbitmq-consumer.plain.stub
php artisan vendor:publish --tag=stubs --provider="BatataHub\\RabbitMQ\\Providers\\RabbitMQServiceProvider"
```

---

## Configuration

After publishing, tweak `config/rabbitmq.php`. You can define multiple connections and map consumers to them.

```php
return [
    'connections' => [
        'default' => [
            'host' => env('RABBITMQ_HOST', '127.0.0.1'),
            'port' => env('RABBITMQ_PORT', 5672),
            'username' => env('RABBITMQ_USER', 'guest'),
            'password' => env('RABBITMQ_PASS', 'guest'),
            'vhost' => env('RABBITMQ_VHOST', '/'),
        ],
        'another' => [
            'host' => env('RABBITMQ_ANOTHER_HOST', '127.0.0.1'),
            'port' => env('RABBITMQ_ANOTHER_PORT', 5672),
            'username' => env('RABBITMQ_ANOTHER_USER', 'guest'),
            'password' => env('RABBITMQ_ANOTHER_PASS', 'guest'),
            'vhost' => env('RABBITMQ_ANOTHER_VHOST', '/'),
        ],
    ],

    'consumers' => [
        [
            'connection' => 'default',
            'queue' => 'UserCreatedQueue',
            'handler' => \App\RabbitMQ\Consumers\UserCreatedConsumer::class,
            // 'noAck' => false,   // default: broker waits for explicit ack
            // 'exclusive' => false,
            // 'nowait' => false,
            // 'consumerTag' => 'my_consumer_tag',
            // 'arguments' => [ 'x-retries' => 3 ],
            // 'ticket' => 42,
        ],

        // Example mapped to another connection
        [
            'connection' => 'another',
            'queue' => 'EmailQueue',
            'handler' => \App\RabbitMQ\Consumers\EmailConsumer::class,
            'noAck' => true,     // auto-ack at broker (handler won't need to ack)
        ],
    ],
];
```

Environment variables you may set in `.env`:

```env
RABBITMQ_HOST=127.0.0.1
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASS=guest
RABBITMQ_VHOST=/
```

---

## Generating Consumers

Generate a consumer class (you can pass nested paths using `/`):

```bash
php artisan rabbitmq:make-consumer UserCreatedConsumer
# or
php artisan rabbitmq:make-consumer Sub/Path/EmailConsumer
```

Generated file will live under `app/RabbitMQ/Consumers/...` with namespace `App\RabbitMQ\Consumers`.

Example handler with manual ack/nack control:

```php
<?php

namespace App\RabbitMQ\Consumers;

use PhpAmqpLib\Message\AMQPMessage;

class UserCreatedConsumer
{
    public function handle(AMQPMessage $message): void
    {
        try {
            $payload = json_decode($message->getBody(), true);

            // Process your payload...

            // Confirm to the broker that processing succeeded
            $message->ack();
        } catch (\Throwable $e) {
            // Reject; requeue=true. Adjust to your retry policy.
            $message->nack(false, true);
            throw $e;
        }
    }
}
```

Notes:
- If you prefer broker auto-ack, set `'noAck' => true` for the consumer in config; then you should not call `ack()`.
- With `'noAck' => false` (default), you must call `ack()` or `nack()` in the handler.

---

## Consuming via Artisan

Consume all configured consumers for a connection:

```bash
php artisan rabbitmq:consume default
```

Consume only a specific queue for that connection:

```bash
php artisan rabbitmq:consume default UserCreatedQueue
```

What happens:
- The command looks up consumers in `config/rabbitmq.php` filtered by `{connection}` and optional `{queue}`.
- For each match, it creates the handler and starts consuming.
- Your handler is called for each message; you control `ack()`/`nack()` unless `noAck=true`.

---

## Publishing Messages

Use the `RabbitMQService` via constructor injection anywhere in your app. Messages are published as JSON (`content_type: application/json`).

```php
<?php

use BatataHub\RabbitMQ\Services\RabbitMQService;

class UserController
{
    public function store(RabbitMQService $rabbit)
    {
        // Setup topology if needed
        $rabbit
            ->declareExchange('users', 'topic')
            ->declareQueue('UserCreatedQueue')
            ->bindQueue('UserCreatedQueue', 'users', 'user.created')

            // Publish payload to the exchange with routing key
            ->publish('users', 'user.created', [
                'id' => 123,
                'name' => 'Jane',
            ]);

        // ...
    }
}
```

---

## Programmatic Consumption

You can also consume without the Artisan command:

```php
<?php

use BatataHub\RabbitMQ\Services\RabbitMQService;
use PhpAmqpLib\Message\AMQPMessage;

class Worker
{
    public function __invoke(RabbitMQService $rabbit): void
    {
        $rabbit->consume('UserCreatedQueue', function (AMQPMessage $message) {
            $data = json_decode($message->getBody(), true);

            // ... process ...

            $message->ack();
        });

        $rabbit->startConsuming();
    }
}
```

---

## Version Compatibility

| Laravel | PHP   | Package |
|---------|-------|---------|
| 12.x    | 8.1+  | ^1.0    |
| 11.x    | 8.1+  | ^1.0    |
| 10.x    | 8.1+  | ^1.0    |

Relies on `php-amqplib/php-amqplib:^3.0`.

---

## FAQ

- How are messages encoded?
  - Arrays are JSON-encoded. `content_type` is set to `application/json`.

- Who controls ack/nack?
  - You do, inside your consumer handler, unless you set `noAck=true` in the config to enable broker auto-ack.

- Can I customize the consumer stub?
  - Yes. Publish the stub with the `stubs` tag, edit it in your app, and the generator will use your customized version.

---

## License

MIT


