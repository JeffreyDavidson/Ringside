<?php

declare(strict_types=1);

use App\Livewire\Concerns\ShowTableTrait;

/**
 * Unit tests for ShowTableTrait structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Trait structure verification
 * - Method signatures and return types
 * - Configuration method existence
 * - Trait naming and namespace
 *
 * @see ShowTableTrait
 * @see \Tests\Integration\Livewire\Concerns\ShowTableTraitTest
 */
describe('ShowTableTrait Unit Tests', function () {
    describe('trait structure', function () {
        test('is trait', function () {
            $reflection = new ReflectionClass(ShowTableTrait::class);
            expect($reflection->isTrait())->toBeTrue();
        });

        test('is not abstract', function () {
            $reflection = new ReflectionClass(ShowTableTrait::class);
            expect($reflection->isAbstract())->toBeFalse();
        });
    });

    describe('method signatures', function () {
        test('has configuringShowTableTrait method', function () {
            $reflection = new ReflectionClass(ShowTableTrait::class);
            
            expect($reflection->hasMethod('configuringShowTableTrait'))->toBeTrue();
            
            $method = $reflection->getMethod('configuringShowTableTrait');
            expect($method->isPublic())->toBeTrue();
            expect($method->getReturnType()->getName())->toBe('void');
            expect($method->getNumberOfParameters())->toBe(0);
        });
    });

    describe('namespace and naming', function () {
        test('uses correct namespace', function () {
            $reflection = new ReflectionClass(ShowTableTrait::class);
            expect($reflection->getNamespaceName())->toBe('App\\Livewire\\Concerns');
        });

        test('follows trait naming convention', function () {
            $reflection = new ReflectionClass(ShowTableTrait::class);
            expect($reflection->getShortName())->toBe('ShowTableTrait');
        });
    });

    describe('method implementation structure', function () {
        test('configuringShowTableTrait contains expected method calls', function () {
            $reflection = new ReflectionClass(ShowTableTrait::class);
            $source = file_get_contents($reflection->getFileName());
            
            // Check for expected configuration calls
            expect($source)->toContain('->setPrimaryKey(\'id\')');
            expect($source)->toContain('->setColumnSelectDisabled()');
            expect($source)->toContain('->setSearchPlaceholder(\'Search \'.$this->resourceName)');
            expect($source)->toContain('->setPaginationEnabled()');
            expect($source)->toContain('->addAdditionalSelects([$this->databaseTableName.\'.id as id\'])');
            expect($source)->toContain('->setPerPageAccepted([5, 10, 25, 50, 100])');
            expect($source)->toContain('->setLoadingPlaceholderContent(\'Loading\')');
            expect($source)->toContain('->setLoadingPlaceholderEnabled()');
        });
    });

    describe('configuration pattern', function () {
        test('uses fluent interface pattern', function () {
            $reflection = new ReflectionClass(ShowTableTrait::class);
            $source = file_get_contents($reflection->getFileName());
            
            // Check for method chaining pattern
            expect($source)->toContain('$this->setPrimaryKey(\'id\')');
            expect($source)->toContain('->setColumnSelectDisabled()');
            expect($source)->toContain('->setSearchPlaceholder');
            expect($source)->toContain('->setPaginationEnabled()');
        });

        test('references expected properties', function () {
            $reflection = new ReflectionClass(ShowTableTrait::class);
            $source = file_get_contents($reflection->getFileName());
            
            // Check for property references
            expect($source)->toContain('$this->resourceName');
            expect($source)->toContain('$this->databaseTableName');
        });
    });

    describe('laravel livewire tables integration', function () {
        test('follows Laravel Livewire Tables configuration pattern', function () {
            $reflection = new ReflectionClass(ShowTableTrait::class);
            $source = file_get_contents($reflection->getFileName());
            
            // Check for standard table configuration methods
            expect($source)->toContain('setPrimaryKey');
            expect($source)->toContain('setColumnSelectDisabled');
            expect($source)->toContain('setSearchPlaceholder');
            expect($source)->toContain('setPaginationEnabled');
            expect($source)->toContain('setPerPageAccepted');
            expect($source)->toContain('setLoadingPlaceholder');
        });
    });

    describe('trait method organization', function () {
        test('has single public configuration method', function () {
            $reflection = new ReflectionClass(ShowTableTrait::class);
            $publicMethods = array_filter(
                $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
                fn($method) => $method->getDeclaringClass()->getName() === ShowTableTrait::class
            );
            
            expect($publicMethods)->toHaveCount(1);
            expect($publicMethods[0]->getName())->toBe('configuringShowTableTrait');
        });

        test('has no private methods', function () {
            $reflection = new ReflectionClass(ShowTableTrait::class);
            $privateMethods = array_filter(
                $reflection->getMethods(ReflectionMethod::IS_PRIVATE),
                fn($method) => $method->getDeclaringClass()->getName() === ShowTableTrait::class
            );
            
            expect($privateMethods)->toHaveCount(0);
        });
    });

    describe('trait simplicity', function () {
        test('is minimal focused trait', function () {
            $reflection = new ReflectionClass(ShowTableTrait::class);
            
            // Should have minimal methods (just the configuring method)
            $methods = array_filter(
                $reflection->getMethods(),
                fn($method) => $method->getDeclaringClass()->getName() === ShowTableTrait::class
            );
            
            expect($methods)->toHaveCount(1);
        });

        test('has no properties', function () {
            $reflection = new ReflectionClass(ShowTableTrait::class);
            $properties = array_filter(
                $reflection->getProperties(),
                fn($property) => $property->getDeclaringClass()->getName() === ShowTableTrait::class
            );
            
            expect($properties)->toHaveCount(0);
        });
    });
});