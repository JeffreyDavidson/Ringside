<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionEnum;
use ReflectionMethod;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class RingsideMakeTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ringside:make:test 
                           {name? : The name of the test (optional)}
                           {--unit : Create a unit test}
                           {--feature : Create a feature test}
                           {--model= : Generate a model test for the specified model}
                           {--directory= : Specify model directory (e.g., Users for Users/User)}
                           {--action= : Generate an action test for the specified action}
                           {--repository= : Generate a repository test for the specified repository}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate standardized Ringside tests (models, actions, repositories)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filesystem = new Filesystem();

        // Determine test type and entity with interactive prompts
        if ($this->option('model')) {
            $directory = $this->option('directory');

            return $this->generateModelTest($this->option('model'), $filesystem, $directory);
        }

        if ($this->option('action')) {
            $this->error('Action test generation not yet implemented.');

            return 1;
        }

        if ($this->option('repository')) {
            $this->error('Repository test generation not yet implemented.');

            return 1;
        }

        // No specific test type provided - show interactive prompts
        return $this->handleInteractiveMode($filesystem);
    }

    /**
     * Handle interactive mode when no specific options are provided.
     */
    protected function handleInteractiveMode(Filesystem $filesystem): int
    {
        $this->info('🚀 Ringside Test Generator');
        $this->line('');

        // Ask for test type
        $testType = select(
            label: 'What type of test would you like to generate?',
            options: [
                'model' => 'Model test (data layer validation)',
                'action' => 'Action test (coming soon)',
                'repository' => 'Repository test (coming soon)',
            ],
            default: 'model'
        );

        if ($testType === 'action') {
            $this->error('Action test generation not yet implemented.');

            return 1;
        }

        if ($testType === 'repository') {
            $this->error('Repository test generation not yet implemented.');

            return 1;
        }

        // Handle model test type
        if ($testType === 'model') {
            return $this->handleInteractiveModelTest($filesystem);
        }

        return 1;
    }

    /**
     * Handle interactive model test generation.
     */
    protected function handleInteractiveModelTest(Filesystem $filesystem): int
    {
        // Ask for model name
        $modelName = text(
            label: 'Which model would you like to test?',
            placeholder: 'e.g., Product, User, EventMatchResult',
            required: true,
            validate: fn (string $value) => match (true) {
                empty(mb_trim($value)) => 'Model name is required.',
                ! preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $value) => 'Model name must be a valid class name.',
                default => null
            }
        );

        // Ask for optional directory
        $directory = text(
            label: 'Specify model directory (optional)?',
            placeholder: 'e.g., Users (for Users/User), leave empty for auto-detection',
            required: false,
            validate: fn (string $value) => match (true) {
                ! empty($value) && ! preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $value) => 'Directory must be a valid name.',
                default => null
            }
        );

        $directory = empty(mb_trim($directory)) ? null : mb_trim($directory);

        // Try to resolve the model class
        $modelClass = $this->resolveModelClass($modelName, $directory);

        if (! class_exists($modelClass)) {
            $this->error("Model class {$modelClass} does not exist.");

            $action = select(
                label: 'What would you like to do?',
                options: [
                    'manual' => 'Enter full namespace manually',
                    'create' => 'Create the model first (run make:model)',
                    'cancel' => 'Cancel',
                ],
                default: 'manual'
            );

            if ($action === 'cancel') {
                $this->info('Test generation cancelled.');

                return 0;
            }

            if ($action === 'create') {
                $this->info("You can create the model with: php artisan make:model {$modelName}");

                return 0;
            }

            if ($action === 'manual') {
                $modelClass = text(
                    label: 'Enter the full model namespace',
                    placeholder: 'e.g., App\\Models\\Products\\Product',
                    required: true,
                    validate: fn (string $value) => class_exists($value) ? null : 'Class does not exist.'
                );

                // Extract model name from namespace
                $modelName = class_basename($modelClass);
            }
        }

        $this->line('');
        $this->info("✨ Generating standardized model test for: {$modelName}");
        $this->line("📍 Model class: {$modelClass}");
        $this->line('');

        return $this->generateModelTest($modelName, $filesystem, $directory);
    }

    /**
     * Generate a model unit test.
     */
    protected function generateModelTest(string $modelName, Filesystem $filesystem, ?string $directory = null): int
    {
        // Validate unit flag is present
        if (! $this->option('unit')) {
            $this->error('Model tests currently only support --unit flag');

            return 1;
        }

        // Resolve model class and namespace
        $modelClass = $this->resolveModelClass($modelName, $directory);

        if (! class_exists($modelClass)) {
            $this->error("Model class {$modelClass} does not exist.");

            return 1;
        }

        // Generate test file path
        $testClassName = Str::studly($modelName).'Test';
        $testFilePath = base_path("tests/Unit/Models/{$testClassName}.php");

        // Check if test already exists
        if ($filesystem->exists($testFilePath)) {
            $this->error("Test file already exists: {$testFilePath}");

            return 1;
        }

        // Get stub content and replace tokens
        $stubContent = $this->getModelTestStub();
        $testContent = $this->replaceTokens($stubContent, $modelName, $modelClass, $testClassName);

        // Ensure directory exists
        $filesystem->ensureDirectoryExists(dirname($testFilePath));

        // Write test file
        $filesystem->put($testFilePath, $testContent);

        $this->info("Model unit test created: {$testFilePath}");
        $this->line('');
        $this->info('Generated test includes:');
        $this->line('✓ Table name verification');
        $this->line('✓ Fillable properties testing');
        $this->line('✓ Casts configuration testing');
        $this->line('✓ Custom builder verification');
        $this->line('✓ Default values testing');
        $this->line('✓ Trait integration testing');
        $this->line('✓ Interface implementation testing');
        $this->line('✓ Model constants testing (if any)');
        $this->line('✓ Business methods testing (if any)');
        $this->line('');
        $this->line('Next steps:');
        $this->line('1. Review generated fillable properties array');
        $this->line('2. Verify casts configuration');
        $this->line('3. Update traits list if needed');
        $this->line("4. Run: php artisan test tests/Unit/Models/{$testClassName}.php");

        return 0;
    }

    /**
     * Resolve the full model class name from the provided model name.
     */
    protected function resolveModelClass(string $modelName, ?string $directory = null): string
    {
        // Handle already fully qualified class names
        if (str_contains($modelName, '\\')) {
            return $modelName;
        }

        // If directory is specified, prioritize it
        if ($directory) {
            $directoryPattern = "App\\Models\\{$directory}\\{$modelName}";
            if (class_exists($directoryPattern)) {
                return $directoryPattern;
            }

            // If specified directory doesn't work, continue with normal patterns
            $this->line("⚠️  Model not found in specified directory '{$directory}', trying other locations...");
        }

        // Common model namespace patterns
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
                return $pattern;
            }
        }

        // Default fallback - if directory specified, use it, otherwise use base
        if ($directory) {
            return "App\\Models\\{$directory}\\{$modelName}";
        }

        return "App\\Models\\{$modelName}";
    }

    /**
     * Get the model test stub content.
     */
    protected function getModelTestStub(): string
    {
        $stubPath = base_path('stubs/ringside/test.model.unit.stub');
        $filesystem = new Filesystem();

        if (! $filesystem->exists($stubPath)) {
            // Return a basic stub if the custom stub doesn't exist yet
            return $this->getDefaultModelTestStub();
        }

        return $filesystem->get($stubPath);
    }

    /**
     * Get a default model test stub if custom stub doesn't exist.
     */
    protected function getDefaultModelTestStub(): string
    {
        return '<?php

declare(strict_types=1);

use {{ modelNamespace }};
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Unit tests for {{ modelClass }} model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 * - Interface implementation verification
 *
 * These tests verify that the {{ modelClass }} model is properly configured
 * and structured according to the data layer requirements.
 */
describe(\'{{ modelClass }} Model Unit Tests\', function () {
    describe(\'{{ modelVariable }} attributes and configuration\', function () {
        test(\'{{ modelVariable }} has correct fillable properties\', function () {
            ${{ modelVariable }} = new {{ modelClass }}();
            
            expect(${{ modelVariable }}->getFillable())->toEqual([
                // TODO: Add your model\'s fillable properties here
            ]);
        });

        test(\'{{ modelVariable }} has correct casts configuration\', function () {
            ${{ modelVariable }} = new {{ modelClass }}();
            $casts = ${{ modelVariable }}->getCasts();
            
            // TODO: Add specific cast assertions here
            expect($casts)->toBeArray();
        });

        test(\'{{ modelVariable }} has custom eloquent builder\', function () {
            ${{ modelVariable }} = new {{ modelClass }}();
            // TODO: Verify builder class if one exists
            expect(${{ modelVariable }}->query())->toBeObject();
        });

        test(\'{{ modelVariable }} has correct default values\', function () {
            ${{ modelVariable }} = new {{ modelClass }}();
            // TODO: Add specific default value assertions here
            expect(${{ modelVariable }})->toBeInstanceOf({{ modelClass }}::class);
        });
    });

    describe(\'{{ modelVariable }} trait integration\', function () {
        test(\'{{ modelVariable }} uses all required traits\', function () {
            expect({{ modelClass }}::class)->usesTrait(HasFactory::class);
            // TODO: Add additional trait assertions here
        });

        test(\'{{ modelVariable }} implements all required interfaces\', function () {
            $interfaces = class_implements({{ modelClass }}::class);
            
            // TODO: Add specific interface assertions here
            expect($interfaces)->toBeArray();
        });
    });
});
';
    }

    /**
     * Replace tokens in the stub content.
     */
    protected function replaceTokens(string $stubContent, string $modelName, string $modelClass, string $testClassName): string
    {
        $modelVariable = Str::camel($modelName);
        $modelNamespace = $modelClass;

        // Analyze the model to generate smart content
        $modelInstance = new $modelClass();
        $analysis = $this->analyzeModel($modelClass, $modelInstance);

        $replacements = [
            '{{ modelClass }}' => $modelName,
            '{{ modelVariable }}' => $modelVariable,
            '{{ modelNamespace }}' => $modelNamespace,
            '{{ testClass }}' => $testClassName,
            '{{ tableName }}' => $analysis['tableName'],
            '{{ additionalImports }}' => $analysis['imports'],
            '{{ fillableProperties }}' => $analysis['fillable'],
            '{{ castsAssertions }}' => $analysis['casts'],
            '{{ builderAssertion }}' => $analysis['builder'],
            '{{ defaultValueAssertions }}' => $analysis['defaults'],
            '{{ additionalConfigurationTests }}' => $analysis['additionalTests'],
            '{{ traitAssertions }}' => $analysis['traits'],
            '{{ interfaceAssertions }}' => $analysis['interfaces'],
            '{{ constantsSection }}' => $analysis['constants'],
            '{{ businessMethodsSection }}' => $analysis['businessMethods'],
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $stubContent);
    }

    /**
     * Analyze a model to generate smart test content.
     * 
     * @return array<string, mixed>
     */
    protected function analyzeModel(string $modelClass, object $modelInstance): array
    {
        $reflection = new ReflectionClass($modelClass);

        return [
            'tableName' => $modelInstance->getTable(),
            'imports' => $this->generateImports($reflection),
            'fillable' => $this->generateFillableAssertion($modelInstance),
            'casts' => $this->generateCastsAssertion($modelInstance),
            'builder' => $this->generateBuilderAssertion($modelClass, $modelInstance),
            'defaults' => $this->generateDefaultsAssertion($modelInstance),
            'additionalTests' => $this->generateAdditionalTests($modelInstance),
            'traits' => $this->generateTraitAssertions($reflection),
            'interfaces' => $this->generateInterfaceAssertions($reflection),
            'constants' => $this->generateConstantsSection($reflection),
            'businessMethods' => $this->generateBusinessMethodsSection($reflection),
        ];
    }

    /**
     * Generate import statements for the test.
     */
    /**
     * @param ReflectionClass<object> $reflection
     */
    protected function generateImports(ReflectionClass $reflection): string
    {
        $imports = [];

        // Add trait imports
        $traits = $this->getUsedTraits($reflection);
        foreach ($traits as $trait) {
            $imports[] = "use {$trait};";
        }

        // Add interface imports
        $interfaces = $this->getMeaningfulInterfaces($reflection);
        foreach ($interfaces as $interface) {
            $imports[] = "use {$interface};";
        }

        // Add enum imports for default values
        $modelInstance = new ($reflection->getName())();
        $defaultAttributes = $this->getModelDefaultAttributes($modelInstance);
        foreach ($defaultAttributes as $key => $value) {
            if (is_string($value) && str_contains($value, '::')) {
                $enumClass = explode('::', $value)[0];
                if (class_exists($enumClass) && ! in_array("use {$enumClass};", $imports)) {
                    $imports[] = "use {$enumClass};";
                }
            }
        }

        return empty($imports) ? '' : implode("\n", $imports);
    }

    /**
     * Generate fillable property assertion.
     */
    protected function generateFillableAssertion(object $modelInstance): string
    {
        $fillable = $modelInstance->getFillable();

        if (empty($fillable)) {
            return '// Model has no fillable properties';
        }

        $formattedFillable = array_map(function ($property) {
            return "                '{$property}',";
        }, $fillable);

        return "\n".implode("\n", $formattedFillable)."\n            ";
    }

    /**
     * Generate casts assertions.
     */
    protected function generateCastsAssertion(object $modelInstance): string
    {
        $casts = $modelInstance->getCasts();

        if (empty($casts)) {
            return '// Model has no custom casts
            expect($casts)->toBeArray();';
        }

        $assertions = ['expect($casts)->toBeArray();'];
        foreach ($casts as $attribute => $cast) {
            $assertions[] = "            expect(\$casts['{$attribute}'])->toBe('{$cast}');";
        }

        return implode("\n            ", $assertions);
    }

    /**
     * Generate builder assertion.
     */
    protected function generateBuilderAssertion(string $modelClass, object $modelInstance): string
    {
        // Check if model has a custom builder
        $builderClass = $this->detectCustomBuilder($modelClass);

        if ($builderClass) {
            return "expect(\${$this->getModelVariable($modelClass)}->query())->toBeInstanceOf({$builderClass}::class);";
        }

        return "// Model has no custom builder\n            expect(\${$this->getModelVariable($modelClass)}->query())->toBeObject();";
    }

    /**
     * Generate default values assertion.
     */
    protected function generateDefaultsAssertion(object $modelInstance): string
    {
        $modelClass = get_class($modelInstance);
        $modelVariable = $this->getModelVariable($modelClass);
        $modelShortName = $this->getModelShortName($modelClass);

        // Get default attributes from the model
        $defaultAttributes = $this->getModelDefaultAttributes($modelInstance);

        if (empty($defaultAttributes)) {
            return "// Model has no custom default values\n            expect(\${$modelVariable})->toBeInstanceOf({$modelShortName}::class);";
        }

        $assertions = [];
        foreach ($defaultAttributes as $attribute => $value) {
            // Handle different types of default values
            if (is_string($value)) {
                // Check if it's an enum value
                if (str_contains($value, '::') && class_exists(explode('::', $value)[0])) {
                    $assertions[] = "expect(\${$modelVariable}->{$attribute})->toBe({$value});";
                } else {
                    $assertions[] = "expect(\${$modelVariable}->{$attribute})->toBe('{$value}');";
                }
            } elseif (is_numeric($value)) {
                $assertions[] = "expect(\${$modelVariable}->{$attribute})->toBe({$value});";
            } elseif (is_bool($value)) {
                $boolValue = $value ? 'true' : 'false';
                $assertions[] = "expect(\${$modelVariable}->{$attribute})->toBe({$boolValue});";
            } else {
                // For complex types, just test that the attribute exists
                $assertions[] = "expect(\${$modelVariable}->getAttribute('{$attribute}'))->not->toBeNull();";
            }
        }

        return implode("\n            ", $assertions);
    }

    /**
     * Generate additional configuration tests.
     */
    protected function generateAdditionalTests(object $modelInstance): string
    {
        // No additional tests needed since table name is now handled in the main configuration section
        return '';
    }

    /**
     * Generate trait assertions.
     */
    /**
     * @param ReflectionClass<object> $reflection
     */
    protected function generateTraitAssertions(ReflectionClass $reflection): string
    {
        $traits = $this->getUsedTraits($reflection);

        if (empty($traits)) {
            return '// Model uses no traits';
        }

        $assertions = [];
        foreach ($traits as $trait) {
            $shortName = $this->getShortClassName($trait);
            $assertions[] = "expect({$reflection->getShortName()}::class)->usesTrait({$shortName}::class);";
        }

        return implode("\n            ", $assertions);
    }

    /**
     * Generate interface assertions.
     */
    /**
     * @param ReflectionClass<object> $reflection
     */
    protected function generateInterfaceAssertions(ReflectionClass $reflection): string
    {
        $interfaces = $this->getMeaningfulInterfaces($reflection);

        if (empty($interfaces)) {
            return '$interfaces = class_implements('.$reflection->getShortName().'::class);
            
            // Model implements no custom interfaces
            expect($interfaces)->toBeArray();';
        }

        $assertions = ['$interfaces = class_implements('.$reflection->getShortName().'::class);', ''];
        foreach ($interfaces as $interface) {
            $shortName = $this->getShortClassName($interface);
            $assertions[] = "            expect(\$interfaces)->toContain({$shortName}::class);";
        }

        return implode("\n            ", $assertions);
    }

    /**
     * Get all meaningful traits used by a class (excluding Laravel internal traits).
     */
    /**
     * @param ReflectionClass<object> $reflection
     * @return array<int, string>
     */
    protected function getUsedTraits(ReflectionClass $reflection): array
    {
        // Only get traits directly used by the class, not from parent classes
        $traits = array_keys($reflection->getTraits());

        // Filter to only include meaningful traits that we test for
        $meaningfulTraits = array_filter($traits, function ($trait) {
            return $this->isMeaningfulTrait($trait);
        });

        return array_unique($meaningfulTraits);
    }

    /**
     * Check if a trait is meaningful for our tests (not Laravel internal).
     */
    protected function isMeaningfulTrait(string $trait): bool
    {
        // Include common Laravel traits that we actually test for
        $includedTraits = [
            'Illuminate\Database\Eloquent\Factories\HasFactory',
            'Illuminate\Database\Eloquent\SoftDeletes',
        ];

        // Include our custom application traits
        $appTraitPrefixes = [
            'App\Models\Concerns\\',
        ];

        // Check if it's an explicitly included trait
        if (in_array($trait, $includedTraits)) {
            return true;
        }

        // Check if it's one of our application traits
        foreach ($appTraitPrefixes as $prefix) {
            if (str_starts_with($trait, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect if a model has a custom builder.
     */
    protected function detectCustomBuilder(string $modelClass): ?string
    {
        // Check for UseEloquentBuilder attribute
        $reflection = new ReflectionClass($modelClass);
        $attributes = $reflection->getAttributes();

        foreach ($attributes as $attribute) {
            if ($attribute->getName() === 'Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder') {
                $args = $attribute->getArguments();

                return $args[0] ?? null;
            }
        }

        return null;
    }

    /**
     * Get model variable name from class.
     */
    protected function getModelVariable(string $modelClass): string
    {
        return Str::camel($this->getModelShortName($modelClass));
    }

    /**
     * Get short class name from full class name.
     */
    protected function getModelShortName(string $modelClass): string
    {
        return class_basename($modelClass);
    }

    /**
     * Get short class name for imports.
     */
    protected function getShortClassName(string $fullClassName): string
    {
        return class_basename($fullClassName);
    }

    /**
     * Get meaningful interfaces implemented by a model.
     */
    /**
     * @param ReflectionClass<object> $reflection
     * @return array<int, string>
     */
    protected function getMeaningfulInterfaces(ReflectionClass $reflection): array
    {
        $interfaces = array_keys($reflection->getInterfaces());

        // Filter to only include our custom interfaces
        $meaningfulInterfaces = array_filter($interfaces, function ($interface) {
            return $this->isMeaningfulInterface($interface);
        });

        return array_unique($meaningfulInterfaces);
    }

    /**
     * Check if an interface is meaningful for our tests.
     */
    protected function isMeaningfulInterface(string $interface): bool
    {
        // Include our custom application interfaces
        $appInterfacePrefixes = [
            'App\Models\Contracts\\',
        ];

        foreach ($appInterfacePrefixes as $prefix) {
            if (str_starts_with($interface, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get model default attributes from the attributes property.
     */
    /**
     * @return array<string, mixed>
     */
    protected function getModelDefaultAttributes(object $modelInstance): array
    {
        $reflection = new ReflectionClass($modelInstance);

        // Check if the model has an attributes property
        if (! $reflection->hasProperty('attributes')) {
            return [];
        }

        $property = $reflection->getProperty('attributes');
        $property->setAccessible(true);
        $attributes = $property->getValue($modelInstance);

        if (! is_array($attributes)) {
            return [];
        }

        // Convert enum values to their class references
        $casts = $modelInstance->getCasts();
        $processedAttributes = [];

        foreach ($attributes as $key => $value) {
            if (isset($casts[$key]) && enum_exists($casts[$key])) {
                // Convert enum value to class reference
                $enumClass = $casts[$key];
                $processedAttributes[$key] = $enumClass.'::'.$this->getEnumCaseName($enumClass, $value);
            } else {
                $processedAttributes[$key] = $value;
            }
        }

        return $processedAttributes;
    }

    /**
     * Get enum case name for a value.
     */
    protected function getEnumCaseName(string $enumClass, string $value): string
    {
        if (! enum_exists($enumClass)) {
            return $value;
        }

        $reflection = new ReflectionEnum($enumClass);
        foreach ($reflection->getCases() as $case) {
            // Handle backed enums (have a scalar value)
            if ($case instanceof \ReflectionEnumBackedCase) {
                if ($case->getBackingValue() === $value) {
                    return $case->getName();
                }
            }
            // Handle pure enums (compare case name)
            if ($case->getName() === $value) {
                return $case->getName();
            }
        }

        return $value;
    }

    /**
     * Generate constants section for the test.
     * 
     * @param ReflectionClass<object> $reflection
     */
    protected function generateConstantsSection(ReflectionClass $reflection): string
    {
        $constants = $this->getModelConstants($reflection);

        if (empty($constants)) {
            return '';
        }

        $modelVariable = $this->getModelVariable($reflection->getName());
        $assertions = [];

        foreach ($constants as $name => $value) {
            if (is_string($value)) {
                $assertions[] = "            expect({$reflection->getShortName()}::{$name})->toBe('{$value}');";
            } elseif (is_numeric($value)) {
                $assertions[] = "            expect({$reflection->getShortName()}::{$name})->toBe({$value});";
            } elseif (is_bool($value)) {
                $boolValue = $value ? 'true' : 'false';
                $assertions[] = "            expect({$reflection->getShortName()}::{$name})->toBe({$boolValue});";
            } else {
                $assertions[] = "            expect({$reflection->getShortName()}::{$name})->not->toBeNull();";
            }
        }

        return "
        test('{$modelVariable} has required constants', function () {
".implode("\n", $assertions).'
        });';
    }

    /**
     * Get model constants (excluding Laravel internal constants).
     */
    /**
     * @param ReflectionClass<object> $reflection
     * @return array<string, mixed>
     */
    protected function getModelConstants(ReflectionClass $reflection): array
    {
        $constants = $reflection->getConstants();

        // Filter out Laravel internal constants
        $filteredConstants = [];
        foreach ($constants as $name => $value) {
            if (! $this->isLaravelInternalConstant($name)) {
                $filteredConstants[$name] = $value;
            }
        }

        return $filteredConstants;
    }

    /**
     * Check if a constant is a Laravel internal constant.
     */
    protected function isLaravelInternalConstant(string $name): bool
    {
        $internalConstants = [
            'CREATED_AT',
            'UPDATED_AT',
            'DELETED_AT',
        ];

        return in_array($name, $internalConstants);
    }

    /**
     * Generate business methods section for the test.
     */
    /**
     * @param ReflectionClass<object> $reflection
     */
    protected function generateBusinessMethodsSection(ReflectionClass $reflection): string
    {
        $businessMethods = $this->getBusinessMethods($reflection);

        if (empty($businessMethods)) {
            return '';
        }

        $modelVariable = $this->getModelVariable($reflection->getName());
        $assertions = [];

        foreach ($businessMethods as $method) {
            $assertions[] = "            expect(method_exists({$reflection->getShortName()}::class, '{$method}'))->toBeTrue();";
        }

        return "
    describe('{$modelVariable} business methods', function () {
        test('{$modelVariable} has required business methods', function () {
".implode("\n", $assertions).'
        });
    });';
    }

    /**
     * Get business methods from the model (excluding Laravel framework methods).
     */
    /**
     * @param ReflectionClass<object> $reflection
     * @return array<int, string>
     */
    protected function getBusinessMethods(ReflectionClass $reflection): array
    {
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        $businessMethods = [];

        foreach ($methods as $method) {
            // Only include methods declared in the model class itself
            if ($method->getDeclaringClass()->getName() !== $reflection->getName()) {
                continue;
            }

            $methodName = $method->getName();

            // Skip Laravel framework methods, magic methods, and trait methods
            if (! $this->isLaravelFrameworkMethod($methodName) &&
                ! $this->isMagicMethod($methodName) &&
                ! $this->isTraitMethod($methodName)) {
                $businessMethods[] = $methodName;
            }
        }

        return $businessMethods;
    }

    /**
     * Check if a method is a Laravel framework method.
     */
    protected function isLaravelFrameworkMethod(string $methodName): bool
    {
        $frameworkMethods = [
            // Eloquent methods
            'save', 'update', 'delete', 'restore', 'fresh', 'refresh', 'replicate',
            'toArray', 'toJson', 'jsonSerialize', 'attributesToArray', 'relationsToArray',
            'getAttribute', 'setAttribute', 'getAttributes', 'setRawAttributes',
            'getOriginal', 'getDirty', 'getChanges', 'isDirty', 'wasChanged',
            'fill', 'fillable', 'guarded', 'getFillable', 'getGuarded',
            'newInstance', 'newFromBuilder', 'create', 'forceCreate', 'firstOrNew',
            'firstOrCreate', 'updateOrCreate', 'firstOrFail', 'findOrFail',

            // Query methods
            'newQuery', 'newModelQuery', 'newEloquentBuilder', 'newBaseQueryBuilder',
            'query', 'on', 'onWriteConnection', 'all', 'chunk', 'chunkById',
            'cursor', 'each', 'pluck', 'paginate', 'simplePaginate', 'find',
            'findMany', 'findOrNew', 'first', 'firstWhere', 'value', 'get',
            'count', 'min', 'max', 'sum', 'avg', 'aggregate', 'numericAggregate',
            'increment', 'decrement', 'touch', 'push', 'saveOrFail',

            // Relationship methods
            'hasOne', 'hasMany', 'belongsTo', 'belongsToMany', 'morphTo',
            'morphOne', 'morphMany', 'morphToMany', 'morphedByMany',

            // Accessors/Mutators
            'getTable', 'setTable', 'getKeyName', 'setKeyName', 'getKey',
            'getKeyType', 'setKeyType', 'getIncrementing', 'setIncrementing',
            'getRouteKey', 'getRouteKeyName', 'resolveRouteBinding',
            'resolveSoftDeletableRouteBinding', 'resolveChildRouteBinding',
            'getCreatedAtColumn', 'getUpdatedAtColumn', 'getDeletedAtColumn',
            'usesTimestamps', 'touchOwners', 'getQualifiedKeyName',
            'getQualifiedCreatedAtColumn', 'getQualifiedUpdatedAtColumn',
            'getQualifiedDeletedAtColumn', 'getForeignKey', 'getMorphClass',
            'getConnectionName', 'getConnection', 'setConnection',
            'resolveConnection', 'getConnectionResolver', 'setConnectionResolver',
            'unsetConnectionResolver', 'getEventDispatcher', 'setEventDispatcher',
            'unsetEventDispatcher', 'getMutatedAttributes', 'cacheMutatedAttributes',

            // Casting methods
            'getCasts', 'casts', 'getDates', 'getDateFormat', 'setDateFormat',
            'hasCast', 'getCastType', 'isDateCastable', 'isJsonCastable',
            'isDateAttribute', 'isJsonAttribute', 'isEncryptedCastable',
            'isClassCastable', 'isCustomDateTimeCastable', 'isImmutableCustomDateTimeCastable',
            'isDecimalCastable', 'asDate', 'asDateTime', 'asTimestamp',
            'serializeDate', 'getArrayAttributeValue', 'getArrayAttributeByKey',
            'castAttribute', 'getAttributeFromArray', 'getAttributeValue',
            'getRelationValue', 'isRelation', 'relationLoaded', 'relationResolver',
            'setRelation', 'setRelations', 'unsetRelation', 'getTouchedRelations',
            'setTouchedRelations', 'touches', 'touchOwners', 'newPivot',
            'newRelatedInstance', 'hasGetMutator', 'hasSetMutator',
            'hasAttributeMutator', 'hasAttributeGetMutator', 'hasAttributeSetMutator',
            'mutateAttribute', 'mutateAttributeForArray', 'setMutatedAttributeValue',
            'isClassDeviable', 'isDeviateAttributeCastable', 'deviateClassCastableAttribute',
            'isClassSerializable', 'isEnumCastable', 'getEnumCastableAttributeValue',
            'getClassCastableAttributeValue', 'resolveCasterClass',
            'parseCasterClass', 'mergeAttributesFromClassCasts',
            'mergeAttributesFromAttributeCasts', 'mergeAttributesFromCasts',

            // Events
            'bootTraits', 'bootIfNotBooted', 'boot', 'booted', 'clearBootedModels',
            'flushEventListeners', 'fireModelEvent', 'addObservableEvents',
            'removeObservableEvents', 'registerObserver', 'getObservableEvents',
            'setObservableEvents', 'addObservableEvent', 'removeObservableEvent',
            'observe', 'retrieved', 'saving', 'saved', 'updating', 'updated',
            'creating', 'created', 'deleting', 'deleted', 'trashed', 'restoring',
            'restored', 'replicating', 'registerModelEvent', 'callNamedScope',

            // Scopes
            'globalScopes', 'hasGlobalScope', 'getGlobalScope', 'withGlobalScope',
            'withoutGlobalScope', 'withoutGlobalScopes', 'addGlobalScope',
            'addGlobalScopes', 'removeGlobalScope', 'removeGlobalScopes',
            'bootGlobalScopes', 'scopeQuery', 'applyGlobalScopes',

            // Collection methods
            'newCollection', 'newPivotCollection', 'toBase', 'keys', 'collapse',
            'contains', 'containsStrict', 'diff', 'diffUsing', 'diffAssoc',
            'diffAssocUsing', 'diffKeys', 'diffKeysUsing', 'duplicates',
            'duplicatesStrict', 'except', 'filter', 'when', 'whenEmpty',
            'whenNotEmpty', 'unless', 'unlessEmpty', 'unlessNotEmpty',
            'where', 'whereStrict', 'whereIn', 'whereInStrict', 'whereNotIn',
            'whereNotInStrict', 'whereBetween', 'whereNotBetween', 'whereNull',
            'whereNotNull', 'whereInstanceOf', 'first', 'firstWhere', 'flatten',
            'flip', 'forget', 'forPage', 'get', 'groupBy', 'keyBy', 'has',
            'implode', 'intersect', 'intersectByKeys', 'isEmpty', 'isNotEmpty',
            'join', 'last', 'map', 'mapToDictionary', 'mapWithKeys', 'flatMap',
            'mapInto', 'merge', 'mergeRecursive', 'combine', 'union', 'nth',
            'only', 'pop', 'prepend', 'push', 'concat', 'pull', 'put', 'random',
            'reduce', 'reduceSpread', 'reject', 'reverse', 'search', 'shift',
            'shuffle', 'skip', 'skipUntil', 'skipWhile', 'slice', 'split',
            'sort', 'sortBy', 'sortByDesc', 'sortKeys', 'sortKeysDesc', 'splice',
            'take', 'takeUntil', 'takeWhile', 'tap', 'times', 'toArray', 'toJson',
            'transform', 'unique', 'uniqueStrict', 'unless', 'unlessEmpty',
            'unlessNotEmpty', 'unwrap', 'values', 'when', 'whenEmpty',
            'whenNotEmpty', 'whereStrict', 'wrap', 'zip', 'pad', 'countBy',
            'dd', 'dump', 'each', 'eachSpread', 'every', 'firstOrFail',
            'forgetCertain', 'getFillable', 'getGuarded', 'getHidden', 'getVisible',
        ];

        return in_array($methodName, $frameworkMethods);
    }

    /**
     * Check if a method is a magic method.
     */
    protected function isMagicMethod(string $methodName): bool
    {
        return str_starts_with($methodName, '__');
    }

    /**
     * Check if a method is typically from a trait.
     */
    protected function isTraitMethod(string $methodName): bool
    {
        $traitMethods = [
            // HasFactory trait methods
            'factory',

            // SoftDeletes trait methods
            'bootSoftDeletes', 'initializeSoftDeletes', 'forceDelete', 'forceDestroy',
            'forceDeleteQuietly', 'restoreQuietly', 'restore', 'trashed', 'softDeleted',
            'forceDeleting', 'forceDeleted', 'isForceDeleting',

            // Relationship trait methods (common patterns)
            'managers', 'currentManagers', 'previousManagers', 'fakeManagerPivotModel',
            'stables', 'currentStable', 'previousStables', 'isNotCurrentlyInStable', 'fakeStablePivotModel',
            'titleChampionships', 'currentChampionships', 'currentChampionship', 'previousTitleChampionships', 'isChampion',
            'matches', 'previousMatches', 'canBeBooked', 'cannotBeBooked',
            'wrestlers', 'currentWrestlers', 'previousWrestlers', 'combinedWeight',

            // Employment trait methods
            'employments', 'currentEmployment', 'futureEmployment', 'previousEmployments', 'previousEmployment',
            'firstEmployment', 'hasEmployments', 'isEmployed', 'isCurrentlyEmployed', 'hasFutureEmployment',
            'isNotInEmployment', 'isReleased', 'employmentStartedOn', 'employmentStartedBefore',
            'getFormattedFirstEmployment', 'fakeEmploymentModel', 'hasEmploymentHistory', 'hasStatus',
            'hasAnyStatus', 'doesNotHaveStatus', 'hasNoneOfStatuses',

            // Retirement trait methods
            'retirements', 'currentRetirement', 'previousRetirements', 'previousRetirement',
            'isRetired', 'hasRetirements', 'fakeRetirementModel',

            // Suspension trait methods
            'suspensions', 'currentSuspension', 'previousSuspensions', 'previousSuspension',
            'isSuspended', 'hasSuspensions', 'fakeSuspensionModel',

            // Validation trait methods
            'canBeEmployed', 'ensureCanBeEmployed', 'canBeReleased', 'ensureCanBeReleased',
            'canBeRetired', 'ensureCanBeRetired', 'canBeUnretired', 'ensureCanBeUnretired',
            'canBeSuspended', 'ensureCanBeSuspended', 'canBeReinstated', 'ensureCanBeReinstated',

            // Other trait methods
            'belongsToOne',
        ];

        return in_array($methodName, $traitMethods);
    }
}
