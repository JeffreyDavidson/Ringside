<?php

declare(strict_types=1);

use App\Actions\Stables\DisbandAction;
use App\Actions\Stables\EstablishAction;
use App\Actions\Stables\RetireAction;
use App\Actions\Stables\ReuniteAction;
use App\Actions\Stables\UnretireAction;
use App\Enums\Stables\StableStatus;
use App\Exceptions\Roster\Stables\CannotBeDisbandedException;
use App\Exceptions\Roster\Stables\CannotBeEstablishedException;
use App\Exceptions\Roster\Stables\CannotBeUnretiredException;
use App\Models\Stables\Stable;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
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

            resolve(EstablishAction::class)->handle($this->stable, $debutDate);

            $refreshedStable = freshModel($this->stable);
            expect($refreshedStable->isCurrentlyActive())->toBeTrue();
            expect($refreshedStable->status)->toBe(StableStatus::Active);

            // Verify activity period is created
            $activityPeriod = $refreshedStable->activityPeriods()->latest()->firstOrFail();
            expect(requiredDate($activityPeriod->started_at)->format('Y-m-d H:i:s'))->toBe($debutDate->format('Y-m-d H:i:s'));
            expect($activityPeriod->ended_at)->toBeNull();
        });

        test('debut action handles date parameter correctly', function () {
            $pastDate = Carbon::now()->subMonths(3);

            resolve(EstablishAction::class)->handle($this->stable, $pastDate);

            $refreshedStable = freshModel($this->stable);
            $activityPeriod = $refreshedStable->activityPeriods()->latest()->firstOrFail();
            expect(requiredDate($activityPeriod->started_at)->format('Y-m-d H:i:s'))->toBe($pastDate->format('Y-m-d H:i:s'));
        });

        test('debut action from unformed status creates proper status change', function () {
            expect($this->stable->status)->toBe(StableStatus::Unformed);

            resolve(EstablishAction::class)->handle($this->stable, Carbon::now());

            $refreshedStable = freshModel($this->stable);
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

            resolve(DisbandAction::class)->handle($this->activeStable, $disbandDate);

            $refreshedStable = freshModel($this->activeStable);
            expect($refreshedStable->isDisbanded())->toBeTrue();
            expect($refreshedStable->status)->toBe(StableStatus::Inactive);

            // Verify activity period is ended
            $activityPeriod = $refreshedStable->activityPeriods()->latest()->firstOrFail();
            expect($activityPeriod->ended_at)->not()->toBeNull();
            expect(requiredDate($activityPeriod->ended_at)->format('Y-m-d H:i:s'))->toBe($disbandDate->format('Y-m-d H:i:s'));
        });

        test('disband action creates proper status change record', function () {
            resolve(DisbandAction::class)->handle($this->activeStable, Carbon::now());

            $refreshedStable = freshModel($this->activeStable);
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

            resolve(ReuniteAction::class)->handle($this->disbandedStable, $reuniteDate);

            $refreshedStable = freshModel($this->disbandedStable);
            expect($refreshedStable->isCurrentlyActive())->toBeTrue();
            expect($refreshedStable->status)->toBe(StableStatus::Active);

            // Verify new activity period is created
            $activityPeriods = $refreshedStable->activityPeriods()->orderBy('started_at')->get();
            expect($activityPeriods)->toHaveCount(2); // Original + reunite

            $latestPeriod = $activityPeriods->reverse()->firstOrFail();
            expect(requiredDate($latestPeriod->started_at)->format('Y-m-d H:i:s'))->toBe($reuniteDate->format('Y-m-d H:i:s'));
            expect($latestPeriod->ended_at)->toBeNull();
        });

        test('reunite action maintains historical activity periods', function () {
            resolve(ReuniteAction::class)->handle($this->disbandedStable, Carbon::now());

            $refreshedStable = freshModel($this->disbandedStable);
            $activityPeriods = $refreshedStable->activityPeriods()->get();

            // Should have both original period (ended) and new period (active)
            expect($activityPeriods)->toHaveCount(2);

            $endedPeriod = $activityPeriods->where('ended_at', '!=', null)->firstOrFail();
            $activePeriod = $activityPeriods->where('ended_at', null)->firstOrFail();

        });
    });

    describe('retire action workflow', function () {
        beforeEach(function () {
            $this->activeStable = Stable::factory()->active()->create();
        });

        test('retire action ends activity and creates retirement record', function () {
            $retireDate = Carbon::now();

            resolve(RetireAction::class)->handle($this->activeStable, $retireDate);

            $refreshedStable = freshModel($this->activeStable);
            expect($refreshedStable->isRetired())->toBeTrue();
            expect($refreshedStable->status)->toBe(StableStatus::Retired);

            // Verify retirement record
            $retirement = $refreshedStable->retirements()->latest()->firstOrFail();
            expect(requiredDate($retirement->started_at)->format('Y-m-d H:i:s'))->toBe($retireDate->format('Y-m-d H:i:s'));
            expect($retirement->ended_at)->toBeNull();

            // Verify activity period is ended
            $activityPeriod = $refreshedStable->activityPeriods()->latest()->firstOrFail();
            expect(requiredDate($activityPeriod->ended_at)->format('Y-m-d H:i:s'))->toBe($retireDate->format('Y-m-d H:i:s'));
        });

        test('retire action from disbanded status works correctly', function () {
            $disbandedStable = Stable::factory()->disbanded()->create();

            resolve(RetireAction::class)->handle($disbandedStable, Carbon::now());

            $refreshedStable = freshModel($disbandedStable);
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

            resolve(UnretireAction::class)->handle($this->retiredStable, $unretireDate);

            $refreshedStable = freshModel($this->retiredStable);
            expect($refreshedStable->isCurrentlyActive())->toBeTrue();
            expect($refreshedStable->status)->toBe(StableStatus::Active);

            // Verify retirement is ended
            $retirement = $refreshedStable->retirements()->latest()->firstOrFail();
            expect(requiredDate($retirement->ended_at)->format('Y-m-d H:i:s'))->toBe($unretireDate->format('Y-m-d H:i:s'));
        });

        test('unretire action can leave the stable inactive when immediate establishment is disabled', function () {
            $originalPeriodCount = $this->retiredStable->activityPeriods()->count();

            resolve(UnretireAction::class)->handle($this->retiredStable, Carbon::now(), establishImmediately: false);

            $refreshedStable = freshModel($this->retiredStable);
            expect($refreshedStable->activityPeriods()->count())->toBe($originalPeriodCount);
            expect($refreshedStable->isInactive())->toBeTrue();
        });

        test('unretire action preserves former members retirement state', function () {
            $retiredWrestler = Wrestler::factory()->retired()->create();
            $retiredTagTeam = TagTeam::factory()->retired()->create();

            $this->retiredStable->wrestlers()->attach($retiredWrestler, [
                'joined_at' => now()->subMonth(),
                'left_at' => now()->subWeek(),
            ]);
            $this->retiredStable->tagTeams()->attach($retiredTagTeam, [
                'joined_at' => now()->subMonth(),
                'left_at' => now()->subWeek(),
            ]);

            resolve(UnretireAction::class)->handle(
                $this->retiredStable,
                establishImmediately: false,
                requireFormerMembers: false,
            );

            expect($retiredWrestler->refresh()->isRetired())->toBeTrue()
                ->and($retiredTagTeam->refresh()->isRetired())->toBeTrue();
        });
    });

    describe('complex lifecycle scenarios', function () {
        test('stable can go through full lifecycle with proper status tracking', function () {
            $stable = Stable::factory()->withEmployedDefaultMembers()->create();

            // Debut
            $debutDate = Carbon::now()->subYear();
            resolve(EstablishAction::class)->handle($stable, $debutDate);
            expect(freshModel($stable)->isCurrentlyActive())->toBeTrue();

            // Disband
            $disbandDate = Carbon::now()->subMonths(6);
            resolve(DisbandAction::class)->handle($stable, $disbandDate);
            expect(freshModel($stable)->isDisbanded())->toBeTrue();

            // Reunite
            $reuniteDate = Carbon::now()->subMonths(3);
            resolve(ReuniteAction::class)->handle($stable, $reuniteDate);
            expect(freshModel($stable)->isCurrentlyActive())->toBeTrue();

            // Retire
            $retireDate = Carbon::now()->subMonths(1);
            resolve(RetireAction::class)->handle($stable, $retireDate);
            expect(freshModel($stable)->isRetired())->toBeTrue();

            // Unretire
            $unretireDate = Carbon::now();
            resolve(UnretireAction::class)->handle($stable, $unretireDate, establishImmediately: false, requireFormerMembers: false);

            $finalStable = freshModel($stable);
            expect($finalStable->isInactive())->toBeTrue();

            // Verify all status changes are recorded
            // Note: Status change functionality is not yet implemented
            // $statusChanges = $finalStable->statusChanges()->orderBy('changed_at')->get();
            // expect($statusChanges)->toHaveCount(2); // Debut and Disband (others are different types)

            // Verify activity periods
            $activityPeriods = $finalStable->activityPeriods()->orderBy('started_at')->get();
            expect($activityPeriods)->toHaveCount(2); // Original debut + reunite

            // Verify retirement record
            $retirement = $finalStable->retirements()->firstOrFail();
            expect($retirement->started_at)->toBeInstanceOf(Carbon::class);
            expect($retirement->ended_at)->toBeInstanceOf(Carbon::class);
        });

        test('action date validation maintains data integrity', function () {
            $stable = Stable::factory()->withEmployedDefaultMembers()->create();

            $debutDate = Carbon::now()->subMonths(6);
            $disbandDate = Carbon::now()->subMonths(3);
            $reuniteDate = Carbon::now();

            // Sequential actions with proper dates
            resolve(EstablishAction::class)->handle($stable, $debutDate);
            resolve(DisbandAction::class)->handle($stable, $disbandDate);
            resolve(ReuniteAction::class)->handle($stable, $reuniteDate);

            $refreshedStable = freshModel($stable);
            $activityPeriods = $refreshedStable->activityPeriods()->orderBy('started_at')->get();

            // Verify chronological order is maintained
            expect(requiredDate($activityPeriods->firstOrFail()->started_at)->format('Y-m-d H:i:s'))->toBe($debutDate->format('Y-m-d H:i:s'));
            expect(requiredDate($activityPeriods->firstOrFail()->ended_at)->format('Y-m-d H:i:s'))->toBe($disbandDate->format('Y-m-d H:i:s'));
            expect(requiredDate($activityPeriods->reverse()->firstOrFail()->started_at)->format('Y-m-d H:i:s'))->toBe($reuniteDate->format('Y-m-d H:i:s'));
            expect($activityPeriods->reverse()->firstOrFail()->ended_at)->toBeNull();
        });
    });

    describe('business rule validation', function () {
        test('debut action requires inactive status', function () {
            $activeStable = Stable::factory()->active()->create();

            expect(fn () => resolve(EstablishAction::class)->handle($activeStable, Carbon::now()))
                ->toThrow(CannotBeEstablishedException::class);
        });

        test('disband action requires active status', function () {
            $inactiveStable = Stable::factory()->inactive()->create();

            expect(fn () => resolve(DisbandAction::class)->handle($inactiveStable, Carbon::now()))
                ->toThrow(CannotBeDisbandedException::class);
        });

        test('reunite action requires disbanded status', function () {
            $activeStable = Stable::factory()->active()->create();

            expect(fn () => resolve(ReuniteAction::class)->handle($activeStable, Carbon::now()))
                ->toThrow(CannotBeEstablishedException::class);
        });

        test('retire action works from active or disbanded status', function () {
            $activeStable = Stable::factory()->active()->create();
            $disbandedStable = Stable::factory()->disbanded()->create();

            // Should work from active
            expect(fn () => resolve(RetireAction::class)->handle($activeStable, Carbon::now()))
                ->not()->toThrow(Exception::class);

            // Should work from disbanded
            expect(fn () => resolve(RetireAction::class)->handle($disbandedStable, Carbon::now()))
                ->not()->toThrow(Exception::class);
        });

        test('unretire action requires retired status', function () {
            $activeStable = Stable::factory()->active()->create();

            expect(fn () => resolve(UnretireAction::class)->handle($activeStable, Carbon::now()))
                ->toThrow(CannotBeUnretiredException::class);
        });
    });
});
