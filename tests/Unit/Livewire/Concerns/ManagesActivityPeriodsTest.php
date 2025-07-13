<?php

declare(strict_types=1);

use App\Livewire\Concerns\ManagesActivityPeriods;
use Illuminate\Database\QueryException;

/**
 * Unit tests for ManagesActivityPeriods trait structure.
 *
 * UNIT TEST SCOPE:
 * - Trait structure verification
 * - Method signatures and return types
 * - Method visibility and documentation
 * - Trait naming and namespace
 * - Exception handling annotations
 *
 * @see ManagesActivityPeriods
 * @see \Tests\Integration\Livewire\Concerns\ManagesActivityPeriodsTest
 */
describe('ManagesActivityPeriods Unit Tests', function () {
    describe('trait structure', function () {
        test('is trait', function () {
            $reflection = new ReflectionClass(ManagesActivityPeriods::class);
            expect($reflection->isTrait())->toBeTrue();
        });

        test('is not abstract', function () {
            $reflection = new ReflectionClass(ManagesActivityPeriods::class);
            expect($reflection->isAbstract())->toBeFalse();
        });

        test('has comprehensive documentation', function () {
            $reflection = new ReflectionClass(ManagesActivityPeriods::class);
            $docComment = $reflection->getDocComment();
            
            expect($docComment)->toContain('Trait for managing activity period operations');
            expect($docComment)->toContain('@requires HasActivityPeriods');
            expect($docComment)->toContain('@author');
            expect($docComment)->toContain('@since');
            expect($docComment)->toContain('@example');
        });
    });

    describe('method signatures', function () {
        test('has handleActivityPeriodCreation method', function () {
            $reflection = new ReflectionClass(ManagesActivityPeriods::class);
            
            expect($reflection->hasMethod('handleActivityPeriodCreation'))->toBeTrue();
            
            $method = $reflection->getMethod('handleActivityPeriodCreation');
            expect($method->isProtected())->toBeTrue();
            expect($method->getReturnType()->getName())->toBe('void');
            expect($method->getNumberOfParameters())->toBe(0);
        });
    });

    describe('method documentation', function () {
        test('handleActivityPeriodCreation has comprehensive documentation', function () {
            $reflection = new ReflectionClass(ManagesActivityPeriods::class);
            $method = $reflection->getMethod('handleActivityPeriodCreation');
            $docComment = $method->getDocComment();
            
            expect($docComment)->toContain('Handle activity period creation when creating a new model');
            expect($docComment)->toContain('@throws QueryException');
            expect($docComment)->toContain('@example');
            expect($docComment)->toContain('@see HasActivityPeriods::activityPeriods()');
        });
    });

    describe('namespace and naming', function () {
        test('uses correct namespace', function () {
            $reflection = new ReflectionClass(ManagesActivityPeriods::class);
            expect($reflection->getNamespaceName())->toBe('App\\Livewire\\Concerns');
        });

        test('follows trait naming convention', function () {
            $reflection = new ReflectionClass(ManagesActivityPeriods::class);
            expect($reflection->getShortName())->toBe('ManagesActivityPeriods');
        });
    });

    describe('dependency imports', function () {
        test('imports QueryException', function () {
            $reflection = new ReflectionClass(ManagesActivityPeriods::class);
            $source = file_get_contents($reflection->getFileName());
            
            expect($source)->toContain('use Illuminate\\Database\\QueryException;');
        });
    });

    describe('method implementation structure', function () {
        test('handleActivityPeriodCreation contains expected logic', function () {
            $reflection = new ReflectionClass(ManagesActivityPeriods::class);
            $source = file_get_contents($reflection->getFileName());
            
            // Check for expected implementation details
            expect($source)->toContain('if (! empty($this->start_date))');
            expect($source)->toContain('$this->formModel->activityPeriods()->create([');
            expect($source)->toContain('\'started_at\' => $this->start_date');
        });
    });

    describe('trait dependencies', function () {
        test('expects start_date property', function () {
            $reflection = new ReflectionClass(ManagesActivityPeriods::class);
            $source = file_get_contents($reflection->getFileName());
            
            // Check for property references
            expect($source)->toContain('$this->start_date');
        });

        test('expects formModel property', function () {
            $reflection = new ReflectionClass(ManagesActivityPeriods::class);
            $source = file_get_contents($reflection->getFileName());
            
            // Check for formModel reference
            expect($source)->toContain('$this->formModel');
        });
    });

    describe('trait method organization', function () {
        test('has single protected method', function () {
            $reflection = new ReflectionClass(ManagesActivityPeriods::class);
            $methods = array_filter(
                $reflection->getMethods(),
                fn($method) => $method->getDeclaringClass()->getName() === ManagesActivityPeriods::class
            );
            
            expect($methods)->toHaveCount(1);
            expect($methods[0]->getName())->toBe('handleActivityPeriodCreation');
            expect($methods[0]->isProtected())->toBeTrue();
        });

        test('has no public methods', function () {
            $reflection = new ReflectionClass(ManagesActivityPeriods::class);
            $publicMethods = array_filter(
                $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
                fn($method) => $method->getDeclaringClass()->getName() === ManagesActivityPeriods::class
            );
            
            expect($publicMethods)->toHaveCount(0);
        });

        test('has no private methods', function () {
            $reflection = new ReflectionClass(ManagesActivityPeriods::class);
            $privateMethods = array_filter(
                $reflection->getMethods(ReflectionMethod::IS_PRIVATE),
                fn($method) => $method->getDeclaringClass()->getName() === ManagesActivityPeriods::class
            );
            
            expect($privateMethods)->toHaveCount(0);
        });
    });

    describe('trait simplicity', function () {
        test('is minimal focused trait', function () {
            $reflection = new ReflectionClass(ManagesActivityPeriods::class);
            
            // Should have minimal methods (just the handler)
            $methods = array_filter(
                $reflection->getMethods(),
                fn($method) => $method->getDeclaringClass()->getName() === ManagesActivityPeriods::class
            );
            
            expect($methods)->toHaveCount(1);
        });

        test('has no properties', function () {
            $reflection = new ReflectionClass(ManagesActivityPeriods::class);
            $properties = array_filter(
                $reflection->getProperties(),
                fn($property) => $property->getDeclaringClass()->getName() === ManagesActivityPeriods::class
            );
            
            expect($properties)->toHaveCount(0);
        });
    });

    describe('activity period creation pattern', function () {
        test('uses conditional activity period creation', function () {
            $reflection = new ReflectionClass(ManagesActivityPeriods::class);
            $source = file_get_contents($reflection->getFileName());
            
            // Check for conditional creation pattern
            expect($source)->toContain('if (! empty($this->start_date))');
        });

        test('creates activity period with started_at field', function () {
            $reflection = new ReflectionClass(ManagesActivityPeriods::class);
            $source = file_get_contents($reflection->getFileName());
            
            // Check for correct field mapping
            expect($source)->toContain('\'started_at\' => $this->start_date');
        });

        test('uses activityPeriods relationship', function () {
            $reflection = new ReflectionClass(ManagesActivityPeriods::class);
            $source = file_get_contents($reflection->getFileName());
            
            // Check for correct relationship usage
            expect($source)->toContain('$this->formModel->activityPeriods()');
        });
    });

    describe('exception handling', function () {
        test('documents QueryException throwing', function () {
            $reflection = new ReflectionClass(ManagesActivityPeriods::class);
            $method = $reflection->getMethod('handleActivityPeriodCreation');
            $docComment = $method->getDocComment();
            
            expect($docComment)->toContain('@throws QueryException');
        });
    });
});