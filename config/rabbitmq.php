<?php

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
            'handler' => '\App\RabbitMQ\Consumers\UserCreatedConsumer',
        ],
        [
            'connection' => 'another',
            'queue' => 'EmailQueue',
            'handler' => '\App\RabbitMQ\Consumers\EmailConsumer',
            'consumerTag' => 'email_consumer_tag',
            'noAck' => true,
            'exclusive' => true,
            'nowait' => true,
            'arguments' => [
                'x-retries' => ['I', 3],
            ],
            'ticket' => 42,
        ],
    ],
];