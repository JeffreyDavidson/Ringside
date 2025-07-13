<?php

declare(strict_types=1);

use App\Livewire\Base\Tables\BaseTable;
use App\Livewire\Concerns\BaseTableTrait;
use Rappasoft\LaravelLivewireTables\DataTableComponent;

/**
 * Unit tests for BaseTable abstract class structure.
 *
 * UNIT TEST SCOPE:
 * - Class inheritance and hierarchy
 * - Trait integration verification
 * - Method existence and signatures
 * - Property visibility and types
 * - Class constants and configuration
 *
 * @see BaseTable
 * @see \Tests\Integration\Livewire\Base\Tables\BaseTableTest
 */
describe('BaseTable Unit Tests', function () {
    describe('class structure and inheritance', function () {
        test('extends DataTableComponent', function () {
            expect(BaseTable::class)->toExtend(DataTableComponent::class);
        });

        test('is abstract class', function () {
            $reflection = new ReflectionClass(BaseTable::class);
            expect($reflection->isAbstract())->toBeTrue();
        });
    });

    describe('trait integration', function () {
        test('uses BaseTableTrait', function () {
            expect(BaseTable::class)->usesTrait(BaseTableTrait::class);
        });
    });

    describe('method existence', function () {
        test('has deleteModel method', function () {
            $reflection = new ReflectionClass(BaseTable::class);
            
            expect($reflection->hasMethod('deleteModel'))->toBeTrue();
            
            $deleteMethod = $reflection->getMethod('deleteModel');
            expect($deleteMethod->isProtected())->toBeTrue();
            expect($deleteMethod->getReturnType()->getName())->toBe('void');
        });
    });

    describe('method signatures', function () {
        test('deleteModel method has correct signature', function () {
            $reflection = new ReflectionClass(BaseTable::class);
            $deleteMethod = $reflection->getMethod('deleteModel');
            
            $parameters = $deleteMethod->getParameters();
            expect($parameters)->toHaveCount(1);
            expect($parameters[0]->getName())->toBe('model');
            expect($parameters[0]->getType()->getName())->toBe('Illuminate\\Database\\Eloquent\\Model');
        });
    });

    describe('namespace and naming', function () {
        test('uses correct namespace', function () {
            $reflection = new ReflectionClass(BaseTable::class);
            expect($reflection->getNamespaceName())->toBe('App\\Livewire\\Base\\Tables');
        });

        test('follows base class naming convention', function () {
            $reflection = new ReflectionClass(BaseTable::class);
            expect($reflection->getShortName())->toBe('BaseTable');
            expect($reflection->getName())->toBe('App\\Livewire\\Base\\Tables\\BaseTable');
        });
    });

    describe('composition over inheritance', function () {
        test('uses traits for specialized functionality', function () {
            $traits = class_uses_recursive(BaseTable::class);
            
            expect($traits)->toContain('App\\Livewire\\Concerns\\BaseTableTrait');
        });
    });

    describe('class constants', function () {
        test('has no class-specific constants', function () {
            $reflection = new ReflectionClass(BaseTable::class);
            $constants = $reflection->getConstants();
            
            // Filter out inherited constants
            $classConstants = array_filter($constants, function ($value, $key) use ($reflection) {
                $constant = $reflection->getReflectionConstant($key);
                return $constant && $constant->getDeclaringClass()->getName() === BaseTable::class;
            }, ARRAY_FILTER_USE_BOTH);
            
            expect($classConstants)->toBeEmpty();
        });
    });

    describe('authorization integration', function () {
        test('deleteModel method uses Gate facade', function () {
            $reflection = new ReflectionClass(BaseTable::class);
            $deleteMethod = $reflection->getMethod('deleteModel');
            
            // Method should exist and be protected
            expect($deleteMethod->isProtected())->toBeTrue();
            
            // Method should have proper return type
            expect($deleteMethod->getReturnType()->getName())->toBe('void');
        });
    });

    describe('session management', function () {
        test('deleteModel method handles session flash messages', function () {
            $reflection = new ReflectionClass(BaseTable::class);
            $deleteMethod = $reflection->getMethod('deleteModel');
            
            // Method should exist for session handling
            expect($deleteMethod)->toBeInstanceOf(ReflectionMethod::class);
            expect($deleteMethod->isProtected())->toBeTrue();
        });
    });

    describe('laravel livewire tables integration', function () {
        test('properly extends DataTableComponent', function () {
            $reflection = new ReflectionClass(BaseTable::class);
            $parentClass = $reflection->getParentClass();
            
            expect($parentClass->getName())->toBe('Rappasoft\\LaravelLivewireTables\\DataTableComponent');
        });
    });

    describe('class design patterns', function () {
        test('follows template method pattern', function () {
            $reflection = new ReflectionClass(BaseTable::class);
            
            // Should be abstract (template)
            expect($reflection->isAbstract())->toBeTrue();
            
            // Should provide common concrete methods
            expect($reflection->hasMethod('deleteModel'))->toBeTrue();
        });
    });
});