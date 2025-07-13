<?php

declare(strict_types=1);

use App\Livewire\Base\Tables\BasePreviousStablesTable;
use App\Livewire\Concerns\ShowTableTrait;
use Rappasoft\LaravelLivewireTables\DataTableComponent;

/**
 * Unit tests for BasePreviousStablesTable abstract class structure.
 *
 * UNIT TEST SCOPE:
 * - Class inheritance and hierarchy
 * - Trait integration verification
 * - Property existence and default values
 * - Method signatures and return types
 * - Abstract class requirements
 *
 * @see BasePreviousStablesTable
 * @see \Tests\Integration\Livewire\Base\Tables\BasePreviousStablesTableTest
 */
describe('BasePreviousStablesTable Unit Tests', function () {
    describe('class structure and inheritance', function () {
        test('extends DataTableComponent', function () {
            expect(BasePreviousStablesTable::class)->toExtend(DataTableComponent::class);
        });

        test('is abstract class', function () {
            $reflection = new ReflectionClass(BasePreviousStablesTable::class);
            expect($reflection->isAbstract())->toBeTrue();
        });
    });

    describe('trait integration', function () {
        test('uses ShowTableTrait', function () {
            expect(BasePreviousStablesTable::class)->usesTrait(ShowTableTrait::class);
        });
    });

    describe('property structure', function () {
        test('has resourceName property with correct value', function () {
            $reflection = new ReflectionClass(BasePreviousStablesTable::class);
            
            expect($reflection->hasProperty('resourceName'))->toBeTrue();
            
            $property = $reflection->getProperty('resourceName');
            expect($property->isProtected())->toBeTrue();
            expect($property->hasDefaultValue())->toBeTrue();
            expect($property->getDefaultValue())->toBe('stables');
        });

        test('has databaseTableName property', function () {
            $reflection = new ReflectionClass(BasePreviousStablesTable::class);
            
            expect($reflection->hasProperty('databaseTableName'))->toBeTrue();
            
            $property = $reflection->getProperty('databaseTableName');
            expect($property->isProtected())->toBeTrue();
        });
    });

    describe('method existence', function () {
        test('has configure method', function () {
            $reflection = new ReflectionClass(BasePreviousStablesTable::class);
            
            expect($reflection->hasMethod('configure'))->toBeTrue();
            
            $method = $reflection->getMethod('configure');
            expect($method->isPublic())->toBeTrue();
            expect($method->getReturnType()->getName())->toBe('void');
        });

        test('has columns method', function () {
            $reflection = new ReflectionClass(BasePreviousStablesTable::class);
            
            expect($reflection->hasMethod('columns'))->toBeTrue();
            
            $method = $reflection->getMethod('columns');
            expect($method->isPublic())->toBeTrue();
            expect($method->getReturnType()->getName())->toBe('array');
        });
    });

    describe('namespace and naming', function () {
        test('uses correct namespace', function () {
            $reflection = new ReflectionClass(BasePreviousStablesTable::class);
            expect($reflection->getNamespaceName())->toBe('App\\Livewire\\Base\\Tables');
        });

        test('follows base class naming convention', function () {
            $reflection = new ReflectionClass(BasePreviousStablesTable::class);
            expect($reflection->getShortName())->toBe('BasePreviousStablesTable');
        });
    });

    describe('template method pattern', function () {
        test('follows template method pattern', function () {
            $reflection = new ReflectionClass(BasePreviousStablesTable::class);
            
            // Should be abstract (template)
            expect($reflection->isAbstract())->toBeTrue();
            
            // Should have template methods
            expect($reflection->hasMethod('configure'))->toBeTrue();
            expect($reflection->hasMethod('columns'))->toBeTrue();
        });
    });

    describe('resource configuration', function () {
        test('configured for stables resource', function () {
            $reflection = new ReflectionClass(BasePreviousStablesTable::class);
            $property = $reflection->getProperty('resourceName');
            
            expect($property->getDefaultValue())->toBe('stables');
        });
    });
});