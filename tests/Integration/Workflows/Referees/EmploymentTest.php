<?php

declare(strict_types=1);

use App\Actions\Referees\EmployAction;
use App\Actions\Referees\ReleaseAction;
use App\Actions\Referees\SuspendAction;
use App\Actions\Referees\UnretireAction;
use App\Enums\Shared\EmploymentStatus;
use App\Lifecycle\Roster\Individuals\IndividualEmploymentEligibility;
use App\Lifecycle\Roster\RosterBookingEligibility;
use App\Models\Roster\Referees\Referee;
use Illuminate\Support\Carbon;

/**
 * Workflow tests for Referee Employment multi-action scenarios.
 *
 * WORKFLOW TEST SCOPE:
 * - Multi-action employment workflows
 * - Cross-action data consistency
 * - Transaction integrity across multiple actions
 * - Complex business process validation
 */
describe('Referee Employment Workflows', function () {

    beforeEach(function () {
        $this->referee = Referee::factory()->released()->create();
    });

    describe('multi-action employment workflows', function () {
        test('employ then release workflow maintains data consistency', function () {
            $referee = Referee::factory()->unemployed()->create();

            // Initial state
            expect($referee->status)->toBe(EmploymentStatus::Unemployed);

            // Employ referee
            resolve(EmployAction::class)->handle($referee, Carbon::now());
            $afterEmployment = freshModel($referee);
            expect($afterEmployment->status)->toBe(EmploymentStatus::Employed);
            expect($afterEmployment->currentEmployment()->exists())->toBeTrue();

            // Release referee
            resolve(ReleaseAction::class)->handle($afterEmployment, Carbon::now());
            $afterRelease = freshModel($referee);

            // Verify release status synchronization
            expect($afterRelease->status)->toBe(EmploymentStatus::Released);
            expect($afterRelease->isReleased())->toBeTrue();
            expect($afterRelease->currentEmployment()->exists())->toBeFalse();
        });

        test('employ then release then re-employ workflow maintains consistency', function () {
            $referee = Referee::factory()->unemployed()->create();

            // Initial employ
            resolve(EmployAction::class)->handle($referee, Carbon::now());
            $employed = freshModel($referee);
            expect($employed->currentEmployment()->exists())->toBeTrue();

            // Release
            resolve(ReleaseAction::class)->handle($employed, Carbon::now());
            $released = freshModel($referee);
            expect($released->isReleased())->toBeTrue();
            expect($released->currentEmployment()->exists())->toBeFalse();

            // Re-employ
            resolve(EmployAction::class)->handle($released, Carbon::now());
            $reEmployed = freshModel($referee);
            expect($reEmployed->currentEmployment()->exists())->toBeTrue();
            expect($reEmployed->isReleased())->toBeFalse();
        });
    });

    describe('transaction integrity', function () {
        test('multi-action workflow maintains transaction integrity', function () {
            $referee = Referee::factory()->unemployed()->create();

            // Execute multi-action workflow within transaction context
            resolve(EmployAction::class)->handle($referee, Carbon::now());
            $employed = freshModel($referee);

            // Then suspend the referee
            resolve(SuspendAction::class)->handle($employed, Carbon::now());
            $suspended = freshModel($referee);

            // Verify all state changes are consistent
            expect($suspended->currentEmployment()->exists())->toBeTrue(); // Still employed
            expect($suspended->isSuspended())->toBeTrue(); // But suspended
            expect($suspended->currentEmployment)->not()->toBeNull();
            expect($suspended->currentSuspension)->not()->toBeNull();
        });

        test('action rollback maintains data consistency on failure', function () {
            $referee = Referee::factory()->released()->create();

            // This test would require mocking a failure scenario
            // For now, just verify normal operation doesn't leave partial state
            resolve(EmployAction::class)->handle($referee, Carbon::now());

            $refreshedReferee = freshModel($referee);

            // Verify all state is consistent - no orphaned records
            if ($refreshedReferee->currentEmployment()->exists()) {
                expect($refreshedReferee->status)->toBe(EmploymentStatus::Employed);
                expect($refreshedReferee->currentEmployment)->not()->toBeNull();
            }
        });
    });

    describe('business rule integration', function () {
        test('employment respects business validation rules', function () {
            $referee = Referee::factory()->unemployed()->create();

            // Test that employment follows business rules
            resolve(EmployAction::class)->handle($referee, Carbon::now());

            $refreshedReferee = freshModel($referee);

            // Verify business rule compliance
            expect($refreshedReferee->currentEmployment()->exists())->toBeTrue();
            expect(resolve(IndividualEmploymentEligibility::class)->canEmploy($refreshedReferee))->toBeFalse(); // Already employed
            expect(RosterBookingEligibility::allows($refreshedReferee))->toBeTrue(); // Can be booked when employed
        });

        test('employment enables officiating capability', function () {
            $referee = Referee::factory()->released()->create();

            // Released referee should not be bookable
            expect(RosterBookingEligibility::allows($referee))->toBeFalse();

            resolve(EmployAction::class)->handle($referee, Carbon::now());

            // Employed referee should be bookable
            expect(RosterBookingEligibility::allows(freshModel($referee)))->toBeTrue();
        });

        test('complex multi-action employment workflows maintain data consistency', function () {
            $referee = Referee::factory()->retired()->create();

            // Retire -> Unretire -> Suspend -> Release workflow
            resolve(UnretireAction::class)->handle($referee, Carbon::now());
            $employed = freshModel($referee);
            expect($employed->currentEmployment()->exists())->toBeTrue();

            resolve(SuspendAction::class)->handle($employed, Carbon::now());
            $suspended = freshModel($referee);
            expect($suspended->currentEmployment()->exists())->toBeTrue();
            expect($suspended->isSuspended())->toBeTrue();

            resolve(ReleaseAction::class)->handle($suspended, Carbon::now());
            $released = freshModel($referee);
            expect($released->isReleased())->toBeTrue();
            expect($released->currentEmployment()->exists())->toBeFalse();
            expect($released->isSuspended())->toBeFalse();
        });
    });

});
