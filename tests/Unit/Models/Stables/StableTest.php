<?php

declare(strict_types=1);

use App\Builders\Concerns\HasStatusScopes;
use App\Builders\Roster\StableBuilder;
use App\Enums\Stables\StableStatus;
use App\Models\Concerns\HasActivityPeriods;
use App\Models\Concerns\HasLifecycleTransitions;
use App\Models\Concerns\IsRetirable;
use App\Models\Contracts\Retirable;
use App\Models\Stables\Stable;
use App\Models\Stables\StableTagTeam;
use App\Models\Stables\StableWrestler;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
            expect(class_uses(Stable::class))->toContain(HasActivityPeriods::class)
                ->and(class_uses(Stable::class))->toContain(HasFactory::class)
                ->and(class_uses(Stable::class))->toContain(HasLifecycleTransitions::class)
                ->and(class_uses(Stable::class))->toContain(HasStatusScopes::class)
                ->and(class_uses(Stable::class))->toContain(IsRetirable::class)
                ->and(class_uses(Stable::class))->toContain(SoftDeletes::class);
        });
    });

    test('defines its membership relationships directly', function () {
        $stable = new Stable();

        expect($stable->wrestlers())->toBeInstanceOf(BelongsToMany::class)
            ->and($stable->wrestlers()->getRelated())->toBeInstanceOf(Wrestler::class)
            ->and($stable->wrestlers()->getPivotClass())->toBe(StableWrestler::class)
            ->and($stable->currentWrestlers())->toBeInstanceOf(BelongsToMany::class)
            ->and($stable->previousWrestlers())->toBeInstanceOf(BelongsToMany::class)
            ->and($stable->tagTeams())->toBeInstanceOf(BelongsToMany::class)
            ->and($stable->tagTeams()->getRelated())->toBeInstanceOf(TagTeam::class)
            ->and($stable->tagTeams()->getPivotClass())->toBe(StableTagTeam::class)
            ->and($stable->currentTagTeams())->toBeInstanceOf(BelongsToMany::class)
            ->and($stable->previousTagTeams())->toBeInstanceOf(BelongsToMany::class);
    });

    describe('interface implementation', function () {
        test('implements all required interfaces', function () {
            $interfaces = class_implements(Stable::class);

            expect($interfaces)->toContain(App\Models\Contracts\HasActivityPeriods::class);
            expect($interfaces)->toContain(Retirable::class);
        });
    });

});
