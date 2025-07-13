<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MakeModelTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:model-test {model : The name of the model to test} {--directory= : Specify model directory (e.g., Users for Users/User)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a standardized Ringside model unit test';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $modelName = $this->argument('model');

        $this->info("Generating standardized model test for: {$modelName}");

        // Delegate to the full Ringside command
        $args = [
            '--unit' => true,
            '--model' => $modelName,
        ];

        if ($this->option('directory')) {
            $args['--directory'] = $this->option('directory');
        }

        return $this->call('ringside:make:test', $args);
    }
}
