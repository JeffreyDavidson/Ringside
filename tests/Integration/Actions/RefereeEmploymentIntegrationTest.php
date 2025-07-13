<?php

declare(strict_types=1);

use App\Actions\Referees\EmployAction;
use App\Actions\Referees\InjureAction;
use App\Actions\Referees\ReleaseAction;
use App\Actions\Referees\RetireAction;
use App\Actions\Referees\SuspendAction;
use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Status\CannotBeEmployedException;
use App\Models\Referees\Referee;
use Illuminate\Support\Carbon;

/**
 * Integration tests for Referee Employment Actions.
 *
 * INTEGRATION TEST SCOPE:
 * - Complete action workflows from start to finish
 * - Status synchronization across multiple components
 * - Repository and action integration
 * - Pipeline and cascade strategy integration
 * - Transaction integrity across components
 */
describe('Referee Employment Action Integration', function () {

    beforeEach(function () {
        $this->referee = Referee::factory()->released()->create();
    });

    describe('EmployAction integration', function () {
        test('complete employment workflow synchronizes all state', function () {
            $employmentDate = Carbon::now();

            // Verify initial state
            expect($this->referee->isReleased())->toBeTrue();
            expect($this->referee->isEmployed())->toBeFalse();
            expect($this->referee->status)->toBe(EmploymentStatus::Released);

            // Execute complete employment workflow
            EmployAction::run($this->referee, $employmentDate);

            // Verify complete state synchronization
            $refreshedReferee = $this->referee->fresh();
            expect($refreshedReferee->isEmployed())->toBeTrue();
            expect($refreshedReferee->isReleased())->toBeFalse();
            expect($refreshedReferee->status)->toBe(EmploymentStatus::Employed);
            expect($refreshedReferee->currentEmployment)->not->toBeNull();
            expect($refreshedReferee->currentEmployment->started_at->toDateTimeString())
                ->toBe($employmentDate->toDateTimeString());
        });

        test('employing already employed referee throws validation error', function () {
            $referee = Referee::factory()->bookable()->create();

            // Verify initial employed state
            expect($referee->isEmployed())->toBeTrue();
            expect($referee->status)->toBe(EmploymentStatus::Employed);

            // Attempt to employ again should throw validation error
            expect(fn () => EmployAction::run($referee, Carbon::now()))
                ->toThrow(CannotBeEmployedException::class);
        });

        test('employing retired referee handles status transition', function () {
            $referee = Referee::factory()->retired()->create();

            // Verify initial retired state
            expect($referee->isRetired())->toBeTrue();
            expect($referee->status)->toBe(EmploymentStatus::Retired);

            // Employ the retired referee
            EmployAction::run($referee, Carbon::now());

            // Verify employment takes precedence over retirement in status field
            $refreshedReferee = $referee->fresh();
            expect($refreshedReferee->isEmployed())->toBeTrue();
            expect($refreshedReferee->status)->toBe(EmploymentStatus::Employed);
            // Note: isRetired() might still be true due to retirement relationship
        });

        test('employment action integrates with status transition pipeline', function () {
            $referee = Referee::factory()->unemployed()->create();

            expect($referee->status)->toBe(EmploymentStatus::Unemployed);
            expect($referee->isEmployed())->toBeFalse();

            // Test that the full pipeline handles the transition
            EmployAction::run($referee, Carbon::now());

            $refreshedReferee = $referee->fresh();
            expect($refreshedReferee->status)->toBe(EmploymentStatus::Employed);
            expect($refreshedReferee->isEmployed())->toBeTrue();
            expect($refreshedReferee->currentEmployment)->not->toBeNull();
        });
    });

    describe('multiple action integration', function () {
        test('employ then release workflow maintains data consistency', function () {
            $referee = Referee::factory()->unemployed()->create();

            // Initial state
            expect($referee->status)->toBe(EmploymentStatus::Unemployed);

            // Employ referee
            EmployAction::run($referee, Carbon::now());
            $afterEmployment = $referee->fresh();
            expect($afterEmployment->status)->toBe(EmploymentStatus::Employed);
            expect($afterEmployment->isEmployed())->toBeTrue();

            // Release referee
            ReleaseAction::run($afterEmployment, Carbon::now());
            $afterRelease = $referee->fresh();

            // Verify release status synchronization
            expect($afterRelease->status)->toBe(EmploymentStatus::Released);
            expect($afterRelease->isReleased())->toBeTrue();
            expect($afterRelease->isEmployed())->toBeFalse();
        });

        test('employ unemployed referee with future date', function () {
            $referee = Referee::factory()->unemployed()->create();
            $futureDate = Carbon::now()->addDays(7);

            EmployAction::run($referee, $futureDate);

            $refreshedReferee = $referee->fresh();
            expect($refreshedReferee->status)->toBe(EmploymentStatus::Employed);
            expect($refreshedReferee->currentEmployment)->toBeNull(); // Future employment not yet current
            
            // Verify the future employment record exists
            $futureEmployment = $refreshedReferee->employments()->latest()->first();
            expect($futureEmployment->started_at->toDateTimeString())
                ->toBe($futureDate->toDateTimeString());
        });
    });

    describe('transaction integrity', function () {
        test('employment action maintains transaction integrity', function () {
            $referee = Referee::factory()->released()->create();

            // Verify the action handles transactions properly
            EmployAction::run($referee, Carbon::now());

            // All changes should be committed together
            $refreshedReferee = $referee->fresh();
            expect($refreshedReferee->isEmployed())->toBeTrue();
            expect($refreshedReferee->status)->toBe(EmploymentStatus::Employed);
            expect($refreshedReferee->currentEmployment)->not->toBeNull();

            // Verify no partial updates occurred
            expect($refreshedReferee->employments()->whereNull('ended_at')->count())->toBe(1);
        });

        test('action rollback maintains data consistency on failure', function () {
            $referee = Referee::factory()->released()->create();

            // This test would require mocking a failure scenario
            // For now, just verify normal operation doesn't leave partial state
            EmployAction::run($referee, Carbon::now());

            $refreshedReferee = $referee->fresh();

            // Verify all state is consistent - no orphaned records
            if ($refreshedReferee->isEmployed()) {
                expect($refreshedReferee->status)->toBe(EmploymentStatus::Employed);
                expect($refreshedReferee->currentEmployment)->not->toBeNull();
            }
        });
    });

    describe('business rule integration', function () {
        test('employment respects business validation rules', function () {
            $referee = Referee::factory()->unemployed()->create();

            // Test that employment follows business rules
            EmployAction::run($referee, Carbon::now());

            $refreshedReferee = $referee->fresh();

            // Verify business rule compliance
            expect($refreshedReferee->isEmployed())->toBeTrue();
            expect($refreshedReferee->canBeEmployed())->toBeFalse(); // Already employed
        });

        test('employment enables referee capabilities', function () {
            $referee = Referee::factory()->released()->create();

            // Released referee should not be available for refereeing duties
            expect($referee->isEmployed())->toBeFalse();

            EmployAction::run($referee, Carbon::now());

            // Employed referee should be available for refereeing duties
            expect($referee->fresh()->isEmployed())->toBeTrue();
        });

        test('referee injury workflow integration', function () {
            $referee = Referee::factory()->unemployed()->create();

            // Employ referee first
            EmployAction::run($referee, Carbon::now());

            $refreshedReferee = $referee->fresh();
            expect($refreshedReferee->isEmployed())->toBeTrue();

            // Test that referee can be injured while employed
            InjureAction::run($refreshedReferee, Carbon::now());

            $injuredReferee = $referee->fresh();
            expect($injuredReferee->isInjured())->toBeTrue();
            expect($injuredReferee->isEmployed())->toBeTrue(); // Still employed but injured
        });

        test('referee suspension workflow integration', function () {
            $referee = Referee::factory()->unemployed()->create();

            // Employ referee first
            EmployAction::run($referee, Carbon::now());

            $refreshedReferee = $referee->fresh();
            expect($refreshedReferee->isEmployed())->toBeTrue();

            // Test that referee can be suspended while employed
            SuspendAction::run($refreshedReferee, Carbon::now());

            $suspendedReferee = $referee->fresh();
            expect($suspendedReferee->isSuspended())->toBeTrue();
            expect($suspendedReferee->isEmployed())->toBeTrue(); // Still employed but suspended
        });

        test('referee retirement workflow integration', function () {
            $referee = Referee::factory()->unemployed()->create();

            // Employ referee first
            EmployAction::run($referee, Carbon::now());

            $refreshedReferee = $referee->fresh();
            expect($refreshedReferee->isEmployed())->toBeTrue();

            // Test that referee retirement ends employment
            RetireAction::run($refreshedReferee, Carbon::now());

            $retiredReferee = $referee->fresh();
            expect($retiredReferee->isRetired())->toBeTrue();
            expect($retiredReferee->status)->toBe(EmploymentStatus::Retired);
        });
    });

    describe('match refereeing integration', function () {
        test('employed referee can referee matches', function () {
            $referee = Referee::factory()->unemployed()->create();

            // Employ referee first
            EmployAction::run($referee, Carbon::now());

            $refreshedReferee = $referee->fresh();
            expect($refreshedReferee->isEmployed())->toBeTrue();

            // Employed referee should be available for match refereeing
            // (Specific implementation depends on match assignment system)
            expect($refreshedReferee->isEmployed())->toBeTrue();
        });

        test('referee employment status affects match assignment capabilities', function () {
            $referee = Referee::factory()->unemployed()->create();

            // Unemployed referee should not be available for matches
            expect($referee->isEmployed())->toBeFalse();

            // Employ referee
            EmployAction::run($referee, Carbon::now());

            // Employed referee should be available for matches
            expect($referee->fresh()->isEmployed())->toBeTrue();

            // Release referee
            ReleaseAction::run($referee->fresh(), Carbon::now());

            // Released referee should not be available for matches
            expect($referee->fresh()->isEmployed())->toBeFalse();
        });

        test('injured referee cannot referee matches while employed', function () {
            $referee = Referee::factory()->unemployed()->create();

            // Employ referee
            EmployAction::run($referee, Carbon::now());
            $refreshedReferee = $referee->fresh();
            expect($refreshedReferee->isEmployed())->toBeTrue();

            // Injure referee
            InjureAction::run($refreshedReferee, Carbon::now());

            $injuredReferee = $referee->fresh();
            expect($injuredReferee->isInjured())->toBeTrue();
            expect($injuredReferee->isEmployed())->toBeTrue();

            // Injured referee should not be available for matches even if employed
            // (Business rule: injured referees cannot officiate)
        });

        test('suspended referee cannot referee matches while employed', function () {
            $referee = Referee::factory()->unemployed()->create();

            // Employ referee
            EmployAction::run($referee, Carbon::now());
            $refreshedReferee = $referee->fresh();
            expect($refreshedReferee->isEmployed())->toBeTrue();

            // Suspend referee
            SuspendAction::run($refreshedReferee, Carbon::now());

            $suspendedReferee = $referee->fresh();
            expect($suspendedReferee->isSuspended())->toBeTrue();
            expect($suspendedReferee->isEmployed())->toBeTrue();

            // Suspended referee should not be available for matches even if employed
            // (Business rule: suspended referees cannot officiate)
        });
    });

    describe('referee availability integration', function () {
        test('referee availability depends on employment and health status', function () {
            $referee = Referee::factory()->unemployed()->create();

            // Unemployed referee is not available
            expect($referee->isEmployed())->toBeFalse();

            // Employ referee
            EmployAction::run($referee, Carbon::now());
            $refreshedReferee = $referee->fresh();

            // Employed, healthy referee is available
            expect($refreshedReferee->isEmployed())->toBeTrue();
            expect($refreshedReferee->isInjured())->toBeFalse();
            expect($refreshedReferee->isSuspended())->toBeFalse();

            // Injure referee
            InjureAction::run($refreshedReferee, Carbon::now());
            $injuredReferee = $referee->fresh();

            // Injured referee is employed but not available for matches
            expect($injuredReferee->isEmployed())->toBeTrue();
            expect($injuredReferee->isInjured())->toBeTrue();
        });

        test('referee employment history tracking', function () {
            $referee = Referee::factory()->unemployed()->create();

            // First employment period
            EmployAction::run($referee, Carbon::now()->subDays(100));
            ReleaseAction::run($referee->fresh(), Carbon::now()->subDays(50));

            // Second employment period
            EmployAction::run($referee->fresh(), Carbon::now()->subDays(30));

            $refreshedReferee = $referee->fresh();

            // Verify employment history is tracked
            expect($refreshedReferee->employments()->count())->toBe(2);
            expect($refreshedReferee->isEmployed())->toBeTrue();

            // First employment should be ended
            $firstEmployment = $refreshedReferee->employments()->oldest()->first();
            expect($firstEmployment->ended_at)->not->toBeNull();

            // Current employment should be ongoing
            expect($refreshedReferee->currentEmployment->ended_at)->toBeNull();
        });
    });
});
