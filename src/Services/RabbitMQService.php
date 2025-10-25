<?php

namespace Vendor\RabbitMQ\Services;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQService
{
    /**
     * @var AMQPStreamConnection
     */
    private AMQPStreamConnection $connection;

    /**
     * @var AMQPChannel
     */
    private AMQPChannel $channel;

    /**
     * Constructor
     * @param array $config
     * @return self
     */
    public function __construct(array $config)
    {
        $this->connection = new AMQPStreamConnection(
            $config['host'], $config['port'], $config['username'], $config['password'], $config['vhost']
        );

        $this->channel = $this->connection->channel();
    }

    /**
     * Declare an exchange
     * @param string $name
     * @param string $type direct, topic, fanout, headers
     * @param bool $passive
     * @param bool $durable
     * @param bool $autoDelete
     * @param bool $internal
     * @param bool $nowait
     * @param array $arguments
     * @param int|null $ticket
     * @return self
     */
    public function declareExchange(
        string $name,
        string $type = 'direct',
        bool $passive = false,
        bool $durable = true,
        bool $autoDelete = false,
        bool $internal = false,
        bool $nowait = false,
        array $arguments = [],
        int|null $ticket = null
    ): self
    {
        $this->channel->exchange_declare(
            $name,
            $type,
            $passive,
            $durable,
            $autoDelete,
            $internal,
            $nowait,
            $arguments,
            $ticket
        );

        return $this;
    }

    /**
     * Declare a queue
     * @param string $queue
     * @param bool $passive
     * @param bool $durable
     * @param bool $exclusive
     * @param bool $autoDelete
     * @param bool $nowait
     * @param array $arguments
     * @param int|null $ticket
     * @return self
     */
    public function declareQueue(
        string $queue,
        bool $passive = false,
        bool $durable = true,
        bool $exclusive = false,
        bool $autoDelete = false,
        bool $nowait = false,
        array $arguments = [],
        int|null $ticket = null
    ): self
    {
        $this->channel->queue_declare(
            $queue,
            $passive,
            $durable,
            $exclusive,
            $autoDelete,
            $nowait,
            $arguments,
            $ticket
        );

        return $this;
    }

    /**
     * Bind a queue to an exchange
     * @param string $queue
     * @param string $exchange
     * @param string|null $routingKey
     * @param bool $nowait
     * @param array $arguments
     * @param int|null $ticket
     * @return self
     */
    public function bindQueue(
        string $queue,
        string $exchange,
        ?string $routingKey = null,
        bool $nowait = false,
        array $arguments = [],
        int|null $ticket = null
    ): self
    {
        $this->channel->queue_bind(
            $queue,
            $exchange,
            $routingKey ?? '',
            $nowait,
            $arguments,
            $ticket
        );

        return $this;
    }

    /**
     * Publish a message to an exchange
     * @param string $exchange
     * @param string $routingKey
     * @param array $payload
     * @return self
     */
    public function publish(string $exchange, string $routingKey, array $payload): self
    {
        $msg = new AMQPMessage(json_encode($payload), [
            'content_type' => 'application/json'
        ]);

        $this->channel->basic_publish($msg, $exchange, $routingKey);

        return $this;
    }

    /**
     * Consume messages from a queue
     * @param string $queue
     * @param callable $callback
     * @param string|null $consumerTag
     * @param bool $noAck
     * @param bool $exclusive
     * @param bool $nowait
     * @param array $arguments
     * @param int|null $ticket
     * @return self
     */
    public function consume(string $queue, callable $callback, string|null $consumerTag = null, bool $noAck = false, bool $exclusive = false, bool $nowait = false, array $arguments = [], int|null $ticket = null): self
    {
        $this->channel->basic_consume(
            $queue,
            $consumerTag ?? '',
            false,
            $noAck,
            $exclusive,
            $nowait,
            $callback,
            $ticket,
            $arguments
        );

        return $this;
    }

    /**
     * Start consuming messages
     * @return void
     */
    public function startConsuming()
    {
        while ($this->channel->is_open()) {
            $this->channel->wait();
        }
    }
}