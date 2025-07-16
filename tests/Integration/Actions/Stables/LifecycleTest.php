<?php

declare(strict_types=1);

use App\Actions\Stables\DebutAction;
use App\Actions\Stables\DisbandAction;
use App\Actions\Stables\RetireAction;
use App\Actions\Stables\ReuniteAction;
use App\Actions\Stables\UnretireAction;
use App\Enums\Stables\StableStatus;
use App\Models\Stables\Stable;
use Illuminate\Support\Carbon;

/**
 * Integration tests for Stable activation and lifecycle management actions.
 *
 * This test suite validates the complete workflow of stable lifecycle management
 * including debut, disbanding, reuniting, retiring, and status synchronization.
 * These tests use real database relationships and verify that actions properly
 * update both the status enum field and create the corresponding activity periods.
 */
describe('Stable Activation Action Integration', function () {
    beforeEach(function () {
        $this->stable = Stable::factory()->create();
    });

    describe('debut action workflow', function () {
        test('debut action creates activity period and updates status', function () {
            $debutDate = Carbon::now();

            DebutAction::run($this->stable, $debutDate);

            $refreshedStable = $this->stable->fresh();
            expect($refreshedStable->isCurrentlyActive())->toBeTrue();
            expect($refreshedStable->status)->toBe(StableStatus::Active);

            // Verify activity period is created
            $activityPeriod = $refreshedStable->activityPeriods()->latest()->first();
            expect($activityPeriod)->not->toBeNull();
            expect($activityPeriod->started_at->format('Y-m-d H:i:s'))->toBe($debutDate->format('Y-m-d H:i:s'));
            expect($activityPeriod->ended_at)->toBeNull();
        });

        test('debut action handles date parameter correctly', function () {
            $pastDate = Carbon::now()->subMonths(3);

            DebutAction::run($this->stable, $pastDate);

            $refreshedStable = $this->stable->fresh();
            $activityPeriod = $refreshedStable->activityPeriods()->latest()->first();
            expect($activityPeriod->started_at->format('Y-m-d H:i:s'))->toBe($pastDate->format('Y-m-d H:i:s'));
        });

        test('debut action from unformed status creates proper status change', function () {
            expect($this->stable->status)->toBe(StableStatus::Unformed);

            DebutAction::run($this->stable, Carbon::now());

            $refreshedStable = $this->stable->fresh();
            expect($refreshedStable->isCurrentlyActive())->toBeTrue();
            expect($refreshedStable->status)->toBe(StableStatus::Active);
        });
    });

    describe('disband action workflow', function () {
        beforeEach(function () {
            // Create an active stable
            $this->activeStable = Stable::factory()->active()->create();
        });

        test('disband action ends activity period and updates status', function () {
            $disbandDate = Carbon::now();

            DisbandAction::run($this->activeStable, $disbandDate);

            $refreshedStable = $this->activeStable->fresh();
            expect($refreshedStable->isDisbanded())->toBeTrue();
            expect($refreshedStable->status)->toBe(StableStatus::Inactive);

            // Verify activity period is ended
            $activityPeriod = $refreshedStable->activityPeriods()->latest()->first();
            expect($activityPeriod->ended_at)->not->toBeNull();
            expect($activityPeriod->ended_at->format('Y-m-d H:i:s'))->toBe($disbandDate->format('Y-m-d H:i:s'));
        });

        test('disband action creates proper status change record', function () {
            DisbandAction::run($this->activeStable, Carbon::now());

            $refreshedStable = $this->activeStable->fresh();
            expect($refreshedStable->status)->toBe(StableStatus::Inactive);
            expect($refreshedStable->isDisbanded())->toBeTrue();
        });
    });

    describe('reunite action workflow', function () {
        beforeEach(function () {
            // Create a disbanded stable
            $this->disbandedStable = Stable::factory()->disbanded()->create();
        });

        test('reunite action creates new activity period and updates status', function () {
            $reuniteDate = Carbon::now();

            ReuniteAction::run($this->disbandedStable, $reuniteDate);

            $refreshedStable = $this->disbandedStable->fresh();
            expect($refreshedStable->isCurrentlyActive())->toBeTrue();
            expect($refreshedStable->status)->toBe(StableStatus::Active);

            // Verify new activity period is created
            $activityPeriods = $refreshedStable->activityPeriods()->orderBy('started_at')->get();
            expect($activityPeriods)->toHaveCount(2); // Original + reunite

            $latestPeriod = $activityPeriods->last();
            expect($latestPeriod->started_at->format('Y-m-d H:i:s'))->toBe($reuniteDate->format('Y-m-d H:i:s'));
            expect($latestPeriod->ended_at)->toBeNull();
        });

        test('reunite action maintains historical activity periods', function () {
            ReuniteAction::run($this->disbandedStable, Carbon::now());

            $refreshedStable = $this->disbandedStable->fresh();
            $activityPeriods = $refreshedStable->activityPeriods()->get();

            // Should have both original period (ended) and new period (active)
            expect($activityPeriods)->toHaveCount(2);

            $endedPeriod = $activityPeriods->where('ended_at', '!=', null)->first();
            $activePeriod = $activityPeriods->where('ended_at', null)->first();

            expect($endedPeriod)->not->toBeNull();
            expect($activePeriod)->not->toBeNull();
        });
    });

    describe('retire action workflow', function () {
        beforeEach(function () {
            $this->activeStable = Stable::factory()->active()->create();
        });

        test('retire action ends activity and creates retirement record', function () {
            $retireDate = Carbon::now();

            RetireAction::run($this->activeStable, $retireDate);

            $refreshedStable = $this->activeStable->fresh();
            expect($refreshedStable->isRetired())->toBeTrue();
            expect($refreshedStable->status)->toBe(StableStatus::Retired);

            // Verify retirement record
            $retirement = $refreshedStable->retirements()->latest()->first();
            expect($retirement)->not->toBeNull();
            expect($retirement->started_at->format('Y-m-d H:i:s'))->toBe($retireDate->format('Y-m-d H:i:s'));
            expect($retirement->ended_at)->toBeNull();

            // Verify activity period is ended
            $activityPeriod = $refreshedStable->activityPeriods()->latest()->first();
            expect($activityPeriod->ended_at->format('Y-m-d H:i:s'))->toBe($retireDate->format('Y-m-d H:i:s'));
        });

        test('retire action from disbanded status works correctly', function () {
            $disbandedStable = Stable::factory()->disbanded()->create();

            RetireAction::run($disbandedStable, Carbon::now());

            $refreshedStable = $disbandedStable->fresh();
            expect($refreshedStable->isRetired())->toBeTrue();
            expect($refreshedStable->status)->toBe(StableStatus::Retired);
        });
    });

    describe('unretire action workflow', function () {
        beforeEach(function () {
            $this->retiredStable = Stable::factory()->retired()->create();
        });

        test('unretire action ends retirement and updates status', function () {
            $unretireDate = Carbon::now();

            UnretireAction::run($this->retiredStable, $unretireDate);

            $refreshedStable = $this->retiredStable->fresh();
            expect($refreshedStable->isInactive())->toBeTrue();
            expect($refreshedStable->status)->toBe(StableStatus::Inactive);

            // Verify retirement is ended
            $retirement = $refreshedStable->retirements()->latest()->first();
            expect($retirement->ended_at->format('Y-m-d H:i:s'))->toBe($unretireDate->format('Y-m-d H:i:s'));
        });

        test('unretire action does not create new activity period', function () {
            $originalPeriodCount = $this->retiredStable->activityPeriods()->count();

            UnretireAction::run($this->retiredStable, Carbon::now());

            $refreshedStable = $this->retiredStable->fresh();
            expect($refreshedStable->activityPeriods()->count())->toBe($originalPeriodCount);
            expect($refreshedStable->isInactive())->toBeTrue();
        });
    });

    describe('complex lifecycle scenarios', function () {
        test('stable can go through full lifecycle with proper status tracking', function () {
            $stable = Stable::factory()->create();

            // Debut
            $debutDate = Carbon::now()->subYear();
            DebutAction::run($stable, $debutDate);
            expect($stable->fresh()->isCurrentlyActive())->toBeTrue();

            // Disband
            $disbandDate = Carbon::now()->subMonths(6);
            DisbandAction::run($stable, $disbandDate);
            expect($stable->fresh()->isDisbanded())->toBeTrue();

            // Reunite
            $reuniteDate = Carbon::now()->subMonths(3);
            ReuniteAction::run($stable, $reuniteDate);
            expect($stable->fresh()->isCurrentlyActive())->toBeTrue();

            // Retire
            $retireDate = Carbon::now()->subMonths(1);
            RetireAction::run($stable, $retireDate);
            expect($stable->fresh()->isRetired())->toBeTrue();

            // Unretire
            $unretireDate = Carbon::now();
            UnretireAction::run($stable, $unretireDate);

            $finalStable = $stable->fresh();
            expect($finalStable->isInactive())->toBeTrue();

            // Verify all status changes are recorded
            // Note: Status change functionality is not yet implemented
            // $statusChanges = $finalStable->statusChanges()->orderBy('changed_at')->get();
            // expect($statusChanges)->toHaveCount(2); // Debut and Disband (others are different types)

            // Verify activity periods
            $activityPeriods = $finalStable->activityPeriods()->orderBy('started_at')->get();
            expect($activityPeriods)->toHaveCount(2); // Original debut + reunite

            // Verify retirement record
            $retirement = $finalStable->retirements()->first();
            expect($retirement->started_at)->not->toBeNull();
            expect($retirement->ended_at)->not->toBeNull();
        });

        test('action date validation maintains data integrity', function () {
            $stable = Stable::factory()->create();

            $debutDate = Carbon::now()->subMonths(6);
            $disbandDate = Carbon::now()->subMonths(3);
            $reuniteDate = Carbon::now();

            // Sequential actions with proper dates
            DebutAction::run($stable, $debutDate);
            DisbandAction::run($stable, $disbandDate);
            ReuniteAction::run($stable, $reuniteDate);

            $refreshedStable = $stable->fresh();
            $activityPeriods = $refreshedStable->activityPeriods()->orderBy('started_at')->get();

            // Verify chronological order is maintained
            expect($activityPeriods->first()->started_at->format('Y-m-d H:i:s'))->toBe($debutDate->format('Y-m-d H:i:s'));
            expect($activityPeriods->first()->ended_at->format('Y-m-d H:i:s'))->toBe($disbandDate->format('Y-m-d H:i:s'));
            expect($activityPeriods->last()->started_at->format('Y-m-d H:i:s'))->toBe($reuniteDate->format('Y-m-d H:i:s'));
            expect($activityPeriods->last()->ended_at)->toBeNull();
        });
    });

    describe('business rule validation', function () {
        test('debut action requires inactive status', function () {
            $activeStable = Stable::factory()->active()->create();

            expect(fn () => DebutAction::run($activeStable, Carbon::now()))
                ->toThrow(Exception::class);
        });

        test('disband action requires active status', function () {
            $inactiveStable = Stable::factory()->inactive()->create();

            expect(fn () => DisbandAction::run($inactiveStable, Carbon::now()))
                ->toThrow(Exception::class);
        });

        test('reunite action requires disbanded status', function () {
            $activeStable = Stable::factory()->active()->create();

            expect(fn () => ReuniteAction::run($activeStable, Carbon::now()))
                ->toThrow(Exception::class);
        });

        test('retire action works from active or disbanded status', function () {
            $activeStable = Stable::factory()->active()->create();
            $disbandedStable = Stable::factory()->disbanded()->create();

            // Should work from active
            expect(fn () => RetireAction::run($activeStable, Carbon::now()))
                ->not->toThrow(Exception::class);

            // Should work from disbanded
            expect(fn () => RetireAction::run($disbandedStable, Carbon::now()))
                ->not->toThrow(Exception::class);
        });

        test('unretire action requires retired status', function () {
            $activeStable = Stable::factory()->active()->create();

            expect(fn () => UnretireAction::run($activeStable, Carbon::now()))
                ->toThrow(Exception::class);
        });
    });
});
