<?php

declare(strict_types=1);

use App\Builders\Concerns\HasStatusScopes;
use App\Builders\Roster\StableBuilder;
use App\Enums\Stables\StableStatus;
use App\Models\Concerns\HasActivityPeriods;
use App\Models\Concerns\HasLifecycleTransitions;
use App\Models\Concerns\HasMembers;
use App\Models\Concerns\IsRetirable;
use App\Models\Contracts\Retirable;
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
            ]);
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
            expect(class_uses(Stable::class))->toContain(HasActivityPeriods::class);
            expect(class_uses(Stable::class))->toContain(HasFactory::class);
            expect(class_uses(Stable::class))->toContain(HasMembers::class);
            expect(class_uses(Stable::class))->toContain(HasLifecycleTransitions::class);
            expect(class_uses(Stable::class))->toContain(HasStatusScopes::class);
            expect(class_uses(Stable::class))->toContain(IsRetirable::class);
            expect(class_uses(Stable::class))->toContain(SoftDeletes::class);
        });
    });

    describe('interface implementation', function () {
        test('implements all required interfaces', function () {
            $interfaces = class_implements(Stable::class);

            expect($interfaces)->toContain(App\Models\Contracts\HasActivityPeriods::class);
            expect($interfaces)->toContain(Retirable::class);
        });
    });

});
