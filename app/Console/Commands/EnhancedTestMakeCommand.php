<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Foundation\Console\TestMakeCommand;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;

class EnhancedTestMakeCommand extends TestMakeCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new test class (enhanced with Ringside integration)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->getNameInput();

        // Check if this looks like a model test
        if ($this->isLikelyModelTest($name)) {
            $modelName = $this->extractModelName($name);

            // Check if the model exists
            if ($this->modelExists($modelName)) {
                $useRingside = confirm(
                    label: "This appears to be a model test. Would you like to use Ringside's standardized model test generator instead?",
                    default: true,
                    yes: 'Yes, use Ringside generator (recommended)',
                    no: 'No, create basic Laravel test'
                );

                if ($useRingside) {
                    $this->info('✨ Generating standardized model test with Ringside...');

                    $result = $this->call('ringside:make:test', [
                        '--unit' => $this->option('unit'),
                        '--model' => $modelName,
                    ]);
                    
                    return $result === 0;
                }
            }
        }

        // Fall back to Laravel's default behavior
        return parent::handle();
    }

    /**
     * Check if the test name looks like a model test.
     */
    protected function isLikelyModelTest(string $name): bool
    {
        // Remove "Test" suffix if present
        $baseName = Str::endsWith($name, 'Test') ? Str::replaceLast('Test', '', $name) : $name;

        // Check if it follows model naming patterns and --unit flag is present
        return $this->option('unit') &&
               preg_match('/^[A-Z][a-zA-Z0-9]*$/', $baseName) &&
               ! Str::contains($baseName, ['Controller', 'Action', 'Repository', 'Service']);
    }

    /**
     * Extract the model name from the test name.
     */
    protected function extractModelName(string $name): string
    {
        return Str::endsWith($name, 'Test') ? Str::replaceLast('Test', '', $name) : $name;
    }

    /**
     * Check if a model with the given name exists.
     */
    protected function modelExists(string $modelName): bool
    {
        $patterns = [
            "App\\Models\\{$modelName}",
            "App\\Models\\{$modelName}\\{$modelName}",
            "App\\Models\\Wrestlers\\{$modelName}",
            "App\\Models\\Managers\\{$modelName}",
            "App\\Models\\Referees\\{$modelName}",
            "App\\Models\\TagTeams\\{$modelName}",
            "App\\Models\\Titles\\{$modelName}",
            "App\\Models\\Events\\{$modelName}",
            "App\\Models\\Stables\\{$modelName}",
            "App\\Models\\Shared\\{$modelName}",
            "App\\Models\\Users\\{$modelName}",
            "App\\Models\\Matches\\{$modelName}",
        ];

        foreach ($patterns as $pattern) {
            if (class_exists($pattern)) {
                return true;
            }
        }

        return false;
    }
}
