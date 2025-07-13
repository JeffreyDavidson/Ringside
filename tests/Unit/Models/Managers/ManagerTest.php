<?php

declare(strict_types=1);

use App\Builders\Roster\ManagerBuilder;
use App\Enums\Shared\EmploymentStatus;
use App\Models\Concerns\DefinesManagedAliases;
use App\Models\Concerns\IsEmployable;
use App\Models\Concerns\IsInjurable;
use App\Models\Concerns\IsRetirable;
use App\Models\Concerns\IsSuspendable;
use App\Models\Concerns\ProvidesDisplayName;
use App\Models\Managers\Manager;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Unit tests for Manager model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
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
                'status',
            ]);
        });

        test('has correct casts configuration', function () {
            $manager = new Manager();
            $casts = $manager->getCasts();

            expect($casts['status'])->toBe(EmploymentStatus::class);
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
            expect(Manager::class)->usesTrait(DefinesManagedAliases::class);
            expect(Manager::class)->usesTrait(HasFactory::class);
            expect(Manager::class)->usesTrait(IsEmployable::class);
            expect(Manager::class)->usesTrait(IsInjurable::class);
            expect(Manager::class)->usesTrait(IsRetirable::class);
            expect(Manager::class)->usesTrait(IsSuspendable::class);
            expect(Manager::class)->usesTrait(ProvidesDisplayName::class);
            expect(Manager::class)->usesTrait(SoftDeletes::class);
        });
    });

    describe('interface implementation', function () {
        test('implements all required interfaces', function () {
            $interfaces = class_implements(Manager::class);

            expect($interfaces)->toContain('App\Models\Contracts\Employable');
            expect($interfaces)->toContain('App\Models\Contracts\HasDisplayName');
            expect($interfaces)->toContain('App\Models\Contracts\Injurable');
            expect($interfaces)->toContain('App\Models\Contracts\Retirable');
            expect($interfaces)->toContain('App\Models\Contracts\Suspendable');
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

    describe('business logic methods', function () {
        test('has display name methods from ProvidesDisplayName trait', function () {
            $manager = new Manager();

            expect(method_exists($manager, 'getDisplayName'))->toBeTrue();
        });
    });
});
