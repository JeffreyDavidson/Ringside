<?php

declare(strict_types=1);

use App\Actions\TagTeams\EmployAction;
use App\Actions\TagTeams\ReinstateAction;
use App\Actions\TagTeams\ReleaseAction;
use App\Actions\TagTeams\RetireAction;
use App\Actions\TagTeams\SuspendAction;
use App\Actions\TagTeams\UnretireAction;
use App\Enums\Shared\EmploymentStatus;
use App\Models\TagTeams\TagTeam;
use Illuminate\Support\Carbon;

/**
 * Integration tests for TagTeam employment and lifecycle management actions.
 *
 * This test suite validates the complete workflow of tag team employment management
 * including employing, releasing, retiring, suspending, and status synchronization.
 * These tests use real database relationships and verify that actions properly
 * update both the status enum field and create the corresponding employment periods.
 */
describe('TagTeam Employment Action Integration', function () {
    beforeEach(function () {
        $this->tagTeam = TagTeam::factory()->unemployed()->create();
    });

    describe('employ action workflow', function () {
        test('employ action creates employment period and updates status', function () {
            $employmentDate = Carbon::now();

            EmployAction::run($this->tagTeam, $employmentDate);

            $refreshedTagTeam = $this->tagTeam->fresh();
            expect($refreshedTagTeam->isEmployed())->toBeTrue();
            expect($refreshedTagTeam->status)->toBe(EmploymentStatus::Employed);

            // Verify employment period is created
            $employment = $refreshedTagTeam->employments()->latest()->first();
            expect($employment)->not->toBeNull();
            expect($employment->started_at->toDateTimeString())->toBe($employmentDate->toDateTimeString());
            expect($employment->ended_at)->toBeNull();
        });

        test('employ action handles date parameter correctly', function () {
            $pastDate = Carbon::now()->subMonths(3);

            EmployAction::run($this->tagTeam, $pastDate);

            $refreshedTagTeam = $this->tagTeam->fresh();
            $employment = $refreshedTagTeam->employments()->latest()->first();
            expect($employment->started_at->toDateTimeString())->toBe($pastDate->toDateTimeString());
        });

        test('employ action from unemployed status creates proper employment record', function () {
            expect($this->tagTeam->isUnemployed())->toBeTrue();

            EmployAction::run($this->tagTeam, Carbon::now());

            $refreshedTagTeam = $this->tagTeam->fresh();
            expect($refreshedTagTeam->isEmployed())->toBeTrue();

            // Verify current employment exists
            expect($refreshedTagTeam->currentEmployment)->not->toBeNull();
            expect($refreshedTagTeam->currentEmployment->ended_at)->toBeNull();
        });
    });

    describe('release action workflow', function () {
        beforeEach(function () {
            // Create an employed tag team
            $this->employedTagTeam = TagTeam::factory()->employed()->create();
        });

        test('release action ends employment period and updates status', function () {
            $releaseDate = Carbon::now();

            ReleaseAction::run($this->employedTagTeam, $releaseDate);

            $refreshedTagTeam = $this->employedTagTeam->fresh();
            expect($refreshedTagTeam->isReleased())->toBeTrue();
            expect($refreshedTagTeam->status)->toBe(EmploymentStatus::Released);

            // Verify employment period is ended
            $employment = $refreshedTagTeam->employments()->latest()->first();
            expect($employment->ended_at)->not->toBeNull();
            expect($employment->ended_at->toDateTimeString())->toBe($releaseDate->toDateTimeString());
        });

        test('release action maintains employment history', function () {
            ReleaseAction::run($this->employedTagTeam, Carbon::now());

            $refreshedTagTeam = $this->employedTagTeam->fresh();
            expect($refreshedTagTeam->employments()->count())->toBe(1);
            expect($refreshedTagTeam->currentEmployment)->toBeNull();
            expect($refreshedTagTeam->previousEmployments()->count())->toBe(1);
        });
    });

    describe('suspend action workflow', function () {
        beforeEach(function () {
            $this->employedTagTeam = TagTeam::factory()->employed()->create();
        });

        test('suspend action creates suspension period and updates status', function () {
            $suspensionDate = Carbon::now();

            SuspendAction::run($this->employedTagTeam, $suspensionDate);

            $refreshedTagTeam = $this->employedTagTeam->fresh();
            expect($refreshedTagTeam->isSuspended())->toBeTrue();
            expect($refreshedTagTeam->status)->toBe(EmploymentStatus::Suspended);

            // Verify suspension period is created
            $suspension = $refreshedTagTeam->suspensions()->latest()->first();
            expect($suspension)->not->toBeNull();
            expect($suspension->started_at->toDateTimeString())->toBe($suspensionDate->toDateTimeString());
            expect($suspension->ended_at)->toBeNull();
        });

        test('suspend action maintains employment while suspended', function () {
            SuspendAction::run($this->employedTagTeam, Carbon::now());

            $refreshedTagTeam = $this->employedTagTeam->fresh();

            // Should still have active employment
            expect($refreshedTagTeam->currentEmployment)->not->toBeNull();
            expect($refreshedTagTeam->currentEmployment->ended_at)->toBeNull();
        });
    });

    describe('reinstate action workflow', function () {
        beforeEach(function () {
            $this->suspendedTagTeam = TagTeam::factory()->suspended()->create();
        });

        test('reinstate action ends suspension and updates status', function () {
            $reinstateDate = Carbon::now();

            ReinstateAction::run($this->suspendedTagTeam, $reinstateDate);

            $refreshedTagTeam = $this->suspendedTagTeam->fresh();
            expect($refreshedTagTeam->isEmployed())->toBeTrue();
            expect($refreshedTagTeam->status)->toBe(EmploymentStatus::Employed);

            // Verify suspension is ended
            $suspension = $refreshedTagTeam->suspensions()->latest()->first();
            expect($suspension->ended_at->toDateTimeString())->toBe($reinstateDate->toDateTimeString());
        });

        test('reinstate action maintains employment continuity', function () {
            $originalEmployment = $this->suspendedTagTeam->currentEmployment;

            ReinstateAction::run($this->suspendedTagTeam, Carbon::now());

            $refreshedTagTeam = $this->suspendedTagTeam->fresh();

            // Should have same employment record
            expect($refreshedTagTeam->currentEmployment->id)->toBe($originalEmployment->id);
            expect($refreshedTagTeam->currentEmployment->ended_at)->toBeNull();
        });
    });

    describe('retire action workflow', function () {
        beforeEach(function () {
            $this->employedTagTeam = TagTeam::factory()->employed()->create();
        });

        test('retire action ends employment and creates retirement record', function () {
            $retireDate = Carbon::now();

            RetireAction::run($this->employedTagTeam, $retireDate);

            $refreshedTagTeam = $this->employedTagTeam->fresh();
            expect($refreshedTagTeam->isRetired())->toBeTrue();
            expect($refreshedTagTeam->status)->toBe(EmploymentStatus::Retired);

            // Verify retirement record
            $retirement = $refreshedTagTeam->retirements()->latest()->first();
            expect($retirement)->not->toBeNull();
            expect($retirement->retired_at->toDateTimeString())->toBe($retireDate->toDateTimeString());
            expect($retirement->unretired_at)->toBeNull();

            // Verify employment is ended
            $employment = $refreshedTagTeam->employments()->latest()->first();
            expect($employment->ended_at->toDateTimeString())->toBe($retireDate->toDateTimeString());
        });

        test('retire action from suspended status works correctly', function () {
            $suspendedTagTeam = TagTeam::factory()->suspended()->create();

            RetireAction::run($suspendedTagTeam, Carbon::now());

            $refreshedTagTeam = $suspendedTagTeam->fresh();
            expect($refreshedTagTeam->isRetired())->toBeTrue();
            expect($refreshedTagTeam->status)->toBe(EmploymentStatus::Retired);
        });
    });

    describe('unretire action workflow', function () {
        beforeEach(function () {
            $this->retiredTagTeam = TagTeam::factory()->retired()->create();
        });

        test('unretire action ends retirement and updates status', function () {
            $unretireDate = Carbon::now();

            UnretireAction::run($this->retiredTagTeam, $unretireDate);

            $refreshedTagTeam = $this->retiredTagTeam->fresh();
            expect($refreshedTagTeam->isUnemployed())->toBeTrue();
            expect($refreshedTagTeam->status)->toBe(EmploymentStatus::Unemployed);

            // Verify retirement is ended
            $retirement = $refreshedTagTeam->retirements()->latest()->first();
            expect($retirement->unretired_at->toDateTimeString())->toBe($unretireDate->toDateTimeString());
        });

        test('unretire action does not create new employment', function () {
            $originalEmploymentCount = $this->retiredTagTeam->employments()->count();

            UnretireAction::run($this->retiredTagTeam, Carbon::now());

            $refreshedTagTeam = $this->retiredTagTeam->fresh();
            expect($refreshedTagTeam->employments()->count())->toBe($originalEmploymentCount);
            expect($refreshedTagTeam->currentEmployment)->toBeNull();
        });
    });

    describe('complex employment scenarios', function () {
        test('tag team can go through full employment lifecycle', function () {
            $tagTeam = TagTeam::factory()->unemployed()->create();

            // Employ
            $employDate = Carbon::now()->subYear();
            EmployAction::run($tagTeam, $employDate);
            expect($tagTeam->fresh()->isEmployed())->toBeTrue();

            // Suspend
            $suspendDate = Carbon::now()->subMonths(9);
            SuspendAction::run($tagTeam, $suspendDate);
            expect($tagTeam->fresh()->isSuspended())->toBeTrue();

            // Reinstate
            $reinstateDate = Carbon::now()->subMonths(6);
            ReinstateAction::run($tagTeam, $reinstateDate);
            expect($tagTeam->fresh()->isEmployed())->toBeTrue();

            // Retire
            $retireDate = Carbon::now()->subMonths(3);
            RetireAction::run($tagTeam, $retireDate);
            expect($tagTeam->fresh()->isRetired())->toBeTrue();

            // Unretire
            $unretireDate = Carbon::now();
            UnretireAction::run($tagTeam, $unretireDate);

            $finalTagTeam = $tagTeam->fresh();
            expect($finalTagTeam->isUnemployed())->toBeTrue();

            // Verify all periods are recorded
            expect($finalTagTeam->employments()->count())->toBe(1);
            expect($finalTagTeam->suspensions()->count())->toBe(1);
            expect($finalTagTeam->retirements()->count())->toBe(1);
        });

        test('multiple employment periods with gaps', function () {
            $tagTeam = TagTeam::factory()->unemployed()->create();

            // First employment
            EmployAction::run($tagTeam, Carbon::now()->subYear());
            ReleaseAction::run($tagTeam, Carbon::now()->subMonths(9));

            // Second employment
            EmployAction::run($tagTeam, Carbon::now()->subMonths(6));
            ReleaseAction::run($tagTeam, Carbon::now()->subMonths(3));

            // Current employment
            EmployAction::run($tagTeam, Carbon::now()->subMonths(1));

            $refreshedTagTeam = $tagTeam->fresh();
            expect($refreshedTagTeam->isEmployed())->toBeTrue();
            expect($refreshedTagTeam->employments()->count())->toBe(3);
            expect($refreshedTagTeam->previousEmployments()->count())->toBe(2);
        });

        test('suspension across employment boundaries', function () {
            $tagTeam = TagTeam::factory()->employed()->create();

            // Suspend while employed
            SuspendAction::run($tagTeam, Carbon::now()->subMonths(3));

            // Release while suspended
            ReleaseAction::run($tagTeam, Carbon::now()->subMonths(2));

            $refreshedTagTeam = $tagTeam->fresh();
            expect($refreshedTagTeam->isReleased())->toBeTrue();

            // Both employment and suspension should be ended
            expect($refreshedTagTeam->currentEmployment)->toBeNull();
            expect($refreshedTagTeam->currentSuspension)->toBeNull();

            $latestEmployment = $refreshedTagTeam->employments()->latest()->first();
            $latestSuspension = $refreshedTagTeam->suspensions()->latest()->first();

            expect($latestEmployment->ended_at)->not->toBeNull();
            expect($latestSuspension->ended_at)->not->toBeNull();
        });
    });

    describe('business rule validation', function () {
        test('employ action requires unemployed or released status', function () {
            $employedTagTeam = TagTeam::factory()->employed()->create();

            expect(fn () => EmployAction::run($employedTagTeam, Carbon::now()))
                ->toThrow(Exception::class);
        });

        test('release action requires employed or suspended status', function () {
            $unemployedTagTeam = TagTeam::factory()->unemployed()->create();

            expect(fn () => ReleaseAction::run($unemployedTagTeam, Carbon::now()))
                ->toThrow(Exception::class);
        });

        test('suspend action requires employed status', function () {
            $unemployedTagTeam = TagTeam::factory()->unemployed()->create();

            expect(fn () => SuspendAction::run($unemployedTagTeam, Carbon::now()))
                ->toThrow(Exception::class);
        });

        test('reinstate action requires suspended status', function () {
            $employedTagTeam = TagTeam::factory()->employed()->create();

            expect(fn () => ReinstateAction::run($employedTagTeam, Carbon::now()))
                ->toThrow(Exception::class);
        });

        test('retire action works from employed or suspended status', function () {
            $employedTagTeam = TagTeam::factory()->employed()->create();
            $suspendedTagTeam = TagTeam::factory()->suspended()->create();

            // Should work from employed
            expect(fn () => RetireAction::run($employedTagTeam, Carbon::now()))
                ->not->toThrow(Exception::class);

            // Should work from suspended
            expect(fn () => RetireAction::run($suspendedTagTeam, Carbon::now()))
                ->not->toThrow(Exception::class);
        });

        test('unretire action requires retired status', function () {
            $employedTagTeam = TagTeam::factory()->employed()->create();

            expect(fn () => UnretireAction::run($employedTagTeam, Carbon::now()))
                ->toThrow(Exception::class);
        });
    });

    describe('status synchronization', function () {
        test('all actions properly synchronize status field with relationship state', function () {
            $tagTeam = TagTeam::factory()->unemployed()->create();

            // Each action should sync the status field
            EmployAction::run($tagTeam, Carbon::now()->subYear());
            expect($tagTeam->fresh()->status)->toBe(EmploymentStatus::Employed);

            SuspendAction::run($tagTeam, Carbon::now()->subMonths(9));
            expect($tagTeam->fresh()->status)->toBe(EmploymentStatus::Suspended);

            ReinstateAction::run($tagTeam, Carbon::now()->subMonths(6));
            expect($tagTeam->fresh()->status)->toBe(EmploymentStatus::Employed);

            RetireAction::run($tagTeam, Carbon::now()->subMonths(3));
            expect($tagTeam->fresh()->status)->toBe(EmploymentStatus::Retired);

            UnretireAction::run($tagTeam, Carbon::now());
            expect($tagTeam->fresh()->status)->toBe(EmploymentStatus::Unemployed);
        });

        test('status field remains consistent with relationship state', function () {
            $tagTeam = TagTeam::factory()->unemployed()->create();

            EmployAction::run($tagTeam, Carbon::now());

            $refreshedTagTeam = $tagTeam->fresh();
            expect($refreshedTagTeam->isEmployed())->toBeTrue();
            expect($refreshedTagTeam->status)->toBe(EmploymentStatus::Employed);
            expect($refreshedTagTeam->currentEmployment)->not->toBeNull();
        });
    });
});
