<?php

declare(strict_types=1);

use App\Builders\Roster\StableBuilder;
use App\Database\Query\Concerns\HasStatusScopes;
use App\Enums\Stables\StableStatus;
use App\Models\Concerns\HasActivityPeriods;
use App\Models\Concerns\HasMembers;
use App\Models\Concerns\HasStatusHistory;
use App\Models\Concerns\IsRetirable;
use App\Models\Concerns\ValidatesActivation;
use App\Models\Concerns\ValidatesRetirement;
use App\Models\Stables\Stable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Unit tests for Stable model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 *
 * These tests verify that the Stable model is properly configured
 * and structured according to the data layer requirements.
 */
describe('Stable Model Unit Tests', function () {
    describe('model attributes and configuration', function () {
        test('uses correct table name', function () {
            $stable = new Stable();
            expect($stable->getTable())->toBe('stables');
        });

        test('has correct fillable properties', function () {
            $stable = new Stable();

            expect($stable->getFillable())->toEqual([
                'name',
                'status',
            ]);
        });

        test('has correct casts configuration', function () {
            $stable = new Stable();
            $casts = $stable->getCasts();

            expect($casts['status'])->toBe(StableStatus::class);
        });

        test('has custom eloquent builder', function () {
            $stable = new Stable();
            expect($stable->query())->toBeInstanceOf(StableBuilder::class);
        });

        test('has correct default values', function () {
            $stable = new Stable();
            expect($stable->status)->toBe(StableStatus::Unformed);
        });
    });

    describe('trait integration', function () {
        test('uses all required traits', function () {
            expect(Stable::class)->usesTrait(HasActivityPeriods::class);
            expect(Stable::class)->usesTrait(HasFactory::class);
            expect(Stable::class)->usesTrait(HasMembers::class);
            expect(Stable::class)->usesTrait(HasStatusHistory::class);
            expect(Stable::class)->usesTrait(HasStatusScopes::class);
            expect(Stable::class)->usesTrait(IsRetirable::class);
            expect(Stable::class)->usesTrait(SoftDeletes::class);
            expect(Stable::class)->usesTrait(ValidatesActivation::class);
            expect(Stable::class)->usesTrait(ValidatesRetirement::class);
        });
    });

    describe('interface implementation', function () {
        test('implements all required interfaces', function () {
            $interfaces = class_implements(Stable::class);

            expect($interfaces)->toContain('App\Models\Contracts\Debutable');
            expect($interfaces)->toContain('App\Models\Contracts\HasActivityPeriods');
            expect($interfaces)->toContain('App\Models\Contracts\Retirable');
        });
    });

    describe('model constants', function () {
        test('has MIN_MEMBERS_COUNT constant defined', function () {
            $reflection = new ReflectionClass(Stable::class);
            $constants = $reflection->getConstants();

            // Filter out inherited constants from parent classes
            $modelConstants = array_filter($constants, function ($value, $key) use ($reflection) {
                $constant = $reflection->getReflectionConstant($key);

                return $constant && $constant->getDeclaringClass()->getName() === Stable::class;
            }, ARRAY_FILTER_USE_BOTH);

            expect($modelConstants)->toHaveKey('MIN_MEMBERS_COUNT');
            expect($modelConstants['MIN_MEMBERS_COUNT'])->toBe(3);
        });
    });

    describe('business logic methods', function () {
        test('has member relationship methods', function () {
            $stable = new Stable();

            expect(method_exists($stable, 'wrestlers'))->toBeTrue();
            expect(method_exists($stable, 'tagTeams'))->toBeTrue();
            expect(method_exists($stable, 'currentWrestlers'))->toBeTrue();
            expect(method_exists($stable, 'currentTagTeams'))->toBeTrue();
        });

        test('has activity period methods', function () {
            $stable = new Stable();

            expect(method_exists($stable, 'activityPeriods'))->toBeTrue();
            expect(method_exists($stable, 'isCurrentlyActive'))->toBeTrue();
        });
    });
});
