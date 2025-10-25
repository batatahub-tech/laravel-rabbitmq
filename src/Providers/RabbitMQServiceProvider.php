<?php

namespace Vendor\RabbitMQ\Providers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Vendor\RabbitMQ\Services\RabbitMQService;
use Vendor\RabbitMQ\Console\RabbitMQConsumeCommand;
use Vendor\RabbitMQ\Console\MakeRabbitMQConsumerCommand;

class RabbitMQServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/rabbitmq.php', 'rabbitmq');

        $this->app->singleton(RabbitMQService::class, function ($app) {
            return new RabbitMQService(Config::get('rabbitmq.connections.default'));
        });
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                RabbitMQConsumeCommand::class,
                MakeRabbitMQConsumerCommand::class,
            ]);

            $this->publishes([
                __DIR__ . '/../../config/rabbitmq.php' => App::configPath('rabbitmq.php')
            ], 'config');

            $this->publishes([
                __DIR__ . '/../../stubs/rabbitmq-consumer.plain.stub' => App::basePath('stubs/rabbitmq-consumer.plain.stub'),
            ], 'stubs');
        }
    }
}
