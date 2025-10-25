<?php

namespace Vendor\RabbitMQ\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use function Laravel\Prompts\text;

class MakeRabbitMQConsumerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:rabbitmq-consumer {name?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new RabbitMQ consumer class';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name') ?? text(
            label: 'What is your consumer class name?',
            placeholder: 'UserCreatedConsumer or Sub/Path/UserCreatedConsumer',
            required: true
        );

        $className = Str::studly(class_basename($name));
        $directory = Str::replaceLast($className, '', $name);

        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_\/]*$/', $name)) {
            $this->error('Invalid consumer name. Use only letters, numbers, underscores, and forward slashes.');
            return self::FAILURE;
        }

        $baseNamespace = 'App\\RabbitMQ\\Consumers\\';
        $namespace = rtrim($baseNamespace . str_replace('/', '\\', $directory), '\\');
        $namespace = trim($namespace, '\\');

        $publishedStubPath = App::basePath('stubs/rabbitmq-consumer.plain.stub');
        $packageStubPath = __DIR__ . '/../../stubs/rabbitmq-consumer.plain.stub';
        $stubPath = File::exists($publishedStubPath) ? $publishedStubPath : $packageStubPath;

        if (!File::exists($stubPath)) {
            $this->error('Stub file does not exist.');
            return self::FAILURE;
        }

        try {
            $stub = File::get($stubPath);
        } catch (\Exception $e) {
            $this->error('Could not read the stub file: ' . $e->getMessage());
            return self::FAILURE;
        }

        $stub = str_replace('{{ namespace }}', $namespace, $stub);
        $stub = str_replace('{{ class }}', $className, $stub);

        $targetDir = App::path('RabbitMQ/Consumers/' . str_replace('\\', '/', $directory));
        $targetDir = rtrim($targetDir, '/');
        $targetFile = $targetDir . '/' . $className . '.php';

        if (File::exists($targetFile)) {
            if (!$this->confirm('Consumer already exists. Overwrite?', false)) {
                $this->info('Creation aborted.');
                return self::SUCCESS;
            }
        }

        try {
            if (!File::exists($targetDir)) {
                File::makeDirectory($targetDir, 0755, true);
            }
            File::put($targetFile, $stub);
        } catch (\Exception $e) {
            $this->error('Could not write the consumer file: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->components->info(sprintf('%s [%s] created successfully.', 'RabbitMQ Consumer', $targetFile));

        return self::SUCCESS;
    }
}


