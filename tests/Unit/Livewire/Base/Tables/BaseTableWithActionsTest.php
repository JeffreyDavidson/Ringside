<?php

declare(strict_types=1);

use App\Livewire\Base\Tables\BaseTable;
use App\Livewire\Base\Tables\BaseTableWithActions;
use App\Livewire\Concerns\Columns\HasActionColumn;

/**
 * Unit tests for BaseTableWithActions abstract class structure.
 *
 * UNIT TEST SCOPE:
 * - Class inheritance and hierarchy
 * - Trait integration verification
 * - Property visibility and types
 * - Default property values
 * - Class constants and configuration
 *
 * @see BaseTableWithActions
 * @see \Tests\Integration\Livewire\Base\Tables\BaseTableWithActionsTest
 */
describe('BaseTableWithActions Unit Tests', function () {
    describe('class structure and inheritance', function () {
        test('extends BaseTable', function () {
            expect(BaseTableWithActions::class)->toExtend(BaseTable::class);
        });

        test('is abstract class', function () {
            $reflection = new ReflectionClass(BaseTableWithActions::class);
            expect($reflection->isAbstract())->toBeTrue();
        });
    });

    describe('trait integration', function () {
        test('uses HasActionColumn trait', function () {
            expect(BaseTableWithActions::class)->usesTrait(HasActionColumn::class);
        });
    });

    describe('property structure', function () {
        test('has actionLinksToDisplay property', function () {
            $reflection = new ReflectionClass(BaseTableWithActions::class);
            
            expect($reflection->hasProperty('actionLinksToDisplay'))->toBeTrue();
            
            $property = $reflection->getProperty('actionLinksToDisplay');
            expect($property->isProtected())->toBeTrue();
            expect($property->hasType())->toBeTrue();
            expect($property->getType()->getName())->toBe('array');
        });

        test('has showActionColumn property', function () {
            $reflection = new ReflectionClass(BaseTableWithActions::class);
            
            expect($reflection->hasProperty('showActionColumn'))->toBeTrue();
            
            $property = $reflection->getProperty('showActionColumn');
            expect($property->isProtected())->toBeTrue();
            expect($property->hasType())->toBeTrue();
            expect($property->getType()->getName())->toBe('bool');
        });
    });

    describe('default property values', function () {
        test('actionLinksToDisplay has correct default values', function () {
            $reflection = new ReflectionClass(BaseTableWithActions::class);
            $property = $reflection->getProperty('actionLinksToDisplay');
            
            // Check if property has default value
            expect($property->hasDefaultValue())->toBeTrue();
            
            $defaultValue = $property->getDefaultValue();
            expect($defaultValue)->toBeArray();
            expect($defaultValue)->toHaveKey('view');
            expect($defaultValue)->toHaveKey('edit');
            expect($defaultValue)->toHaveKey('delete');
            expect($defaultValue['view'])->toBeTrue();
            expect($defaultValue['edit'])->toBeTrue();
            expect($defaultValue['delete'])->toBeTrue();
        });

        test('showActionColumn has correct default value', function () {
            $reflection = new ReflectionClass(BaseTableWithActions::class);
            $property = $reflection->getProperty('showActionColumn');
            
            expect($property->hasDefaultValue())->toBeTrue();
            expect($property->getDefaultValue())->toBeTrue();
        });
    });

    describe('property annotations', function () {
        test('actionLinksToDisplay has proper PHPDoc annotation', function () {
            $reflection = new ReflectionClass(BaseTableWithActions::class);
            $property = $reflection->getProperty('actionLinksToDisplay');
            
            $docComment = $property->getDocComment();
            expect($docComment)->toContain('@var array<string, bool>');
        });
    });

    describe('namespace and naming', function () {
        test('uses correct namespace', function () {
            $reflection = new ReflectionClass(BaseTableWithActions::class);
            expect($reflection->getNamespaceName())->toBe('App\\Livewire\\Base\\Tables');
        });

        test('follows base class naming convention', function () {
            $reflection = new ReflectionClass(BaseTableWithActions::class);
            expect($reflection->getShortName())->toBe('BaseTableWithActions');
            expect($reflection->getName())->toBe('App\\Livewire\\Base\\Tables\\BaseTableWithActions');
        });
    });

    describe('composition over inheritance', function () {
        test('uses traits for specialized functionality', function () {
            $traits = class_uses_recursive(BaseTableWithActions::class);
            
            expect($traits)->toContain('App\\Livewire\\Concerns\\Columns\\HasActionColumn');
        });
    });

    describe('class constants', function () {
        test('has no class-specific constants', function () {
            $reflection = new ReflectionClass(BaseTableWithActions::class);
            $constants = $reflection->getConstants();
            
            // Filter out inherited constants
            $classConstants = array_filter($constants, function ($value, $key) use ($reflection) {
                $constant = $reflection->getReflectionConstant($key);
                return $constant && $constant->getDeclaringClass()->getName() === BaseTableWithActions::class;
            }, ARRAY_FILTER_USE_BOTH);
            
            expect($classConstants)->toBeEmpty();
        });
    });

    describe('action configuration', function () {
        test('supports view edit delete actions by default', function () {
            $reflection = new ReflectionClass(BaseTableWithActions::class);
            $property = $reflection->getProperty('actionLinksToDisplay');
            
            $defaultValue = $property->getDefaultValue();
            expect($defaultValue)->toEqual([
                'view' => true,
                'edit' => true,
                'delete' => true,
            ]);
        });

        test('enables action column by default', function () {
            $reflection = new ReflectionClass(BaseTableWithActions::class);
            $property = $reflection->getProperty('showActionColumn');
            
            expect($property->getDefaultValue())->toBeTrue();
        });
    });

    describe('class design patterns', function () {
        test('follows template method pattern', function () {
            $reflection = new ReflectionClass(BaseTableWithActions::class);
            
            // Should be abstract (template)
            expect($reflection->isAbstract())->toBeTrue();
            
            // Should extend BaseTable
            expect($reflection->getParentClass()->getName())->toBe('App\\Livewire\\Base\\Tables\\BaseTable');
        });
    });
});