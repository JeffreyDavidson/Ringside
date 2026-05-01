<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('make:model-test {model : The name of the model to test} {--directory= : Specify model directory (e.g., Users for Users/User)}')]
#[Description('Generate a standardized Ringside model unit test')]
class MakeModelTest extends Command
{
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
