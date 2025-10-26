<?php

namespace BatataHub\RabbitMQ\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use PhpAmqpLib\Message\AMQPMessage;
use BatataHub\RabbitMQ\Services\RabbitMQService;

class RabbitMQConsumeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rabbitmq:consume {connection} {queue?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consume messages from a RabbitMQ queue';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $connectionName = $this->argument('connection');
        $queue = $this->argument('queue');

        $connConfig = Config::get("rabbitmq.connections.{$connectionName}");
        if (!$connConfig) {
            $this->error("Connection '{$connectionName}' not found");
            return self::FAILURE;
        }

        $consumers = collect(Config::get('rabbitmq.consumers', []))
            ->filter(fn ($consumer) => ($consumer['connection'] ?? 'default') === $connectionName);

        if ($queue) {
            $consumers = $consumers->filter(fn ($consumer) => ($consumer['queue'] ?? null) === $queue);
        }

        if ($consumers->isEmpty()) {
            $this->error($queue
                ? "Consumer for queue '{$queue}' on connection '{$connectionName}' not found"
                : "No consumers found for connection '{$connectionName}'"
            );
            return self::FAILURE;
        }

        $service = new RabbitMQService($connConfig);

        foreach ($consumers as $consumer) {
            $handler = new $consumer['handler'];
            $this->info("Consuming messages from queue '{$consumer['queue']}'");
            $service->consume($consumer['queue'], function (AMQPMessage $message) use ($handler) {
                while (true) {
                    $handler->handle($message);
                    break;
                }
            }, $consumer['consumerTag'] ?? null, $consumer['noAck'] ?? false, $consumer['exclusive'] ?? false, $consumer['nowait'] ?? false, $consumer['arguments'] ?? [], $consumer['ticket'] ?? null);
        }

        $this->info('Starting to consume messages...');
        $service->startConsuming();

        return self::SUCCESS;
    }
}
