<?php

declare(strict_types=1);

use App\Builders\Roster\ManagerBuilder;
use App\Enums\Shared\EmploymentStatus;
use App\Models\Concerns\IsEmployable;
use App\Models\Concerns\IsInjurable;
use App\Models\Concerns\IsRetirable;
use App\Models\Concerns\IsSuspendable;
use App\Models\Contracts\Employable;
use App\Models\Contracts\Injurable;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\Suspendable;
use App\Models\Managers\Manager;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Unit tests for Manager model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable and defaults)
 * - Custom builder class verification
 * - Trait integration verification
 *
 * These tests verify that the Manager model is properly configured
 * and structured according to the data layer requirements.
 */
describe('Manager Model Unit Tests', function () {
    describe('model attributes and configuration', function () {
        test('uses correct table name', function () {
            $manager = new Manager();
            expect($manager->getTable())->toBe('managers');
        });

        test('has correct fillable properties', function () {
            $manager = new Manager();

            expect($manager->getFillable())->toEqual([
                'first_name',
                'last_name',
            ]);
        });

        test('has custom eloquent builder', function () {
            $manager = new Manager();
            expect($manager->query())->toBeInstanceOf(ManagerBuilder::class);
        });

        test('has correct default values', function () {
            $manager = new Manager();
            expect($manager->status)->toBe(EmploymentStatus::Unemployed);
        });
    });

    describe('trait integration', function () {
        test('uses all required traits', function () {
            expect(class_uses(Manager::class))->toContain(HasFactory::class);
            expect(class_uses(Manager::class))->toContain(IsEmployable::class);
            expect(class_uses(Manager::class))->toContain(IsInjurable::class);
            expect(class_uses(Manager::class))->toContain(IsRetirable::class);
            expect(class_uses(Manager::class))->toContain(IsSuspendable::class);
            expect(class_uses(Manager::class))->toContain(SoftDeletes::class);
        });
    });

    describe('interface implementation', function () {
        test('implements all required interfaces', function () {
            $interfaces = class_implements(Manager::class);

            expect($interfaces)->toContain(Employable::class);
            expect($interfaces)->toContain(Injurable::class);
            expect($interfaces)->toContain(Retirable::class);
            expect($interfaces)->toContain(Suspendable::class);
        });
    });

    describe('model constants', function () {
        test('has no model-specific constants defined', function () {
            $reflection = new ReflectionClass(Manager::class);
            $constants = $reflection->getConstants();

            // Filter out inherited constants from parent classes
            $modelConstants = array_filter($constants, function ($value, $key) use ($reflection) {
                $constant = $reflection->getReflectionConstant($key);

                return $constant && $constant->getDeclaringClass()->getName() === Manager::class;
            }, ARRAY_FILTER_USE_BOTH);

            expect($modelConstants)->toBeEmpty();
        });
    });

});
