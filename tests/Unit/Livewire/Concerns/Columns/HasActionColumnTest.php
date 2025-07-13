<?php

declare(strict_types=1);

use App\Livewire\Concerns\Columns\HasActionColumn;
use Rappasoft\LaravelLivewireTables\Views\Column;

/**
 * Unit tests for HasActionColumn trait structure.
 *
 * UNIT TEST SCOPE:
 * - Trait structure verification
 * - Method signatures and return types
 * - Method visibility and documentation
 * - Trait naming and namespace
 * - Laravel Livewire Tables integration
 *
 * @see HasActionColumn
 * @see \Tests\Integration\Livewire\Concerns\Columns\HasActionColumnTest
 */
describe('HasActionColumn Unit Tests', function () {
    describe('trait structure', function () {
        test('is trait', function () {
            $reflection = new ReflectionClass(HasActionColumn::class);
            expect($reflection->isTrait())->toBeTrue();
        });

        test('is not abstract', function () {
            $reflection = new ReflectionClass(HasActionColumn::class);
            expect($reflection->isAbstract())->toBeFalse();
        });

        test('has comprehensive documentation', function () {
            $reflection = new ReflectionClass(HasActionColumn::class);
            $docComment = $reflection->getDocComment();
            
            expect($docComment)->toContain('Provides action column functionality for Livewire table components');
            expect($docComment)->toContain('view, edit, and delete links');
        });
    });

    describe('method signatures', function () {
        test('has getDefaultActionColumn method', function () {
            $reflection = new ReflectionClass(HasActionColumn::class);
            
            expect($reflection->hasMethod('getDefaultActionColumn'))->toBeTrue();
            
            $method = $reflection->getMethod('getDefaultActionColumn');
            expect($method->isProtected())->toBeTrue();
            expect($method->getReturnType()->getName())->toBe('Rappasoft\\LaravelLivewireTables\\Views\\Column');
            expect($method->getNumberOfParameters())->toBe(0);
        });
    });

    describe('namespace and naming', function () {
        test('uses correct namespace', function () {
            $reflection = new ReflectionClass(HasActionColumn::class);
            expect($reflection->getNamespaceName())->toBe('App\\Livewire\\Concerns\\Columns');
        });

        test('follows trait naming convention', function () {
            $reflection = new ReflectionClass(HasActionColumn::class);
            expect($reflection->getShortName())->toBe('HasActionColumn');
        });
    });

    describe('dependency imports', function () {
        test('imports Column class', function () {
            $reflection = new ReflectionClass(HasActionColumn::class);
            $source = file_get_contents($reflection->getFileName());
            
            expect($source)->toContain('use Rappasoft\\LaravelLivewireTables\\Views\\Column;');
        });
    });

    describe('method implementation structure', function () {
        test('getDefaultActionColumn creates proper column', function () {
            $reflection = new ReflectionClass(HasActionColumn::class);
            $source = file_get_contents($reflection->getFileName());
            
            // Check for expected implementation details
            expect($source)->toContain('Column::make(__');
            expect($source)->toContain('->label(fn ($row, Column $column)');
            expect($source)->toContain('->html()');
            expect($source)->toContain('->excludeFromColumnSelect()');
        });

        test('uses view component for action column', function () {
            $reflection = new ReflectionClass(HasActionColumn::class);
            $source = file_get_contents($reflection->getFileName());
            
            // Check for view component usage
            expect($source)->toContain('view(\'components.tables.columns.action-column\'');
            expect($source)->toContain('\'path\' => $this->routeBasePath');
            expect($source)->toContain('\'rowId\' => $row->id');
        });
    });

    describe('trait dependencies', function () {
        test('expects routeBasePath property', function () {
            $reflection = new ReflectionClass(HasActionColumn::class);
            $source = file_get_contents($reflection->getFileName());
            
            // Check for property references
            expect($source)->toContain('$this->routeBasePath');
        });

        test('expects row object with id property', function () {
            $reflection = new ReflectionClass(HasActionColumn::class);
            $source = file_get_contents($reflection->getFileName());
            
            // Check for row id reference
            expect($source)->toContain('$row->id');
        });
    });

    describe('trait method organization', function () {
        test('has single protected method', function () {
            $reflection = new ReflectionClass(HasActionColumn::class);
            $methods = array_filter(
                $reflection->getMethods(),
                fn($method) => $method->getDeclaringClass()->getName() === HasActionColumn::class
            );
            
            expect($methods)->toHaveCount(1);
            expect($methods[0]->getName())->toBe('getDefaultActionColumn');
            expect($methods[0]->isProtected())->toBeTrue();
        });

        test('has no public methods', function () {
            $reflection = new ReflectionClass(HasActionColumn::class);
            $publicMethods = array_filter(
                $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
                fn($method) => $method->getDeclaringClass()->getName() === HasActionColumn::class
            );
            
            expect($publicMethods)->toHaveCount(0);
        });

        test('has no private methods', function () {
            $reflection = new ReflectionClass(HasActionColumn::class);
            $privateMethods = array_filter(
                $reflection->getMethods(ReflectionMethod::IS_PRIVATE),
                fn($method) => $method->getDeclaringClass()->getName() === HasActionColumn::class
            );
            
            expect($privateMethods)->toHaveCount(0);
        });
    });

    describe('trait simplicity', function () {
        test('is minimal focused trait', function () {
            $reflection = new ReflectionClass(HasActionColumn::class);
            
            // Should have minimal methods (just the column getter)
            $methods = array_filter(
                $reflection->getMethods(),
                fn($method) => $method->getDeclaringClass()->getName() === HasActionColumn::class
            );
            
            expect($methods)->toHaveCount(1);
        });

        test('has no properties', function () {
            $reflection = new ReflectionClass(HasActionColumn::class);
            $properties = array_filter(
                $reflection->getProperties(),
                fn($property) => $property->getDeclaringClass()->getName() === HasActionColumn::class
            );
            
            expect($properties)->toHaveCount(0);
        });
    });

    describe('laravel livewire tables integration', function () {
        test('uses Column factory method', function () {
            $reflection = new ReflectionClass(HasActionColumn::class);
            $source = file_get_contents($reflection->getFileName());
            
            // Check for Column::make usage
            expect($source)->toContain('Column::make(');
        });

        test('uses internationalization for actions', function () {
            $reflection = new ReflectionClass(HasActionColumn::class);
            $source = file_get_contents($reflection->getFileName());
            
            // Check for translation key
            expect($source)->toContain('__');
            expect($source)->toContain('core.actions');
        });

        test('configures column properly', function () {
            $reflection = new ReflectionClass(HasActionColumn::class);
            $source = file_get_contents($reflection->getFileName());
            
            // Check for proper column configuration
            expect($source)->toContain('->label(');
            expect($source)->toContain('->html()');
            expect($source)->toContain('->excludeFromColumnSelect()');
        });
    });

    describe('column configuration pattern', function () {
        test('uses closure for label generation', function () {
            $reflection = new ReflectionClass(HasActionColumn::class);
            $source = file_get_contents($reflection->getFileName());
            
            // Check for closure pattern
            expect($source)->toContain('fn ($row, Column $column)');
        });

        test('excludes from column selection', function () {
            $reflection = new ReflectionClass(HasActionColumn::class);
            $source = file_get_contents($reflection->getFileName());
            
            // Check for exclusion from selection
            expect($source)->toContain('excludeFromColumnSelect()');
        });

        test('enables HTML rendering', function () {
            $reflection = new ReflectionClass(HasActionColumn::class);
            $source = file_get_contents($reflection->getFileName());
            
            // Check for HTML enabled
            expect($source)->toContain('->html()');
        });
    });
});