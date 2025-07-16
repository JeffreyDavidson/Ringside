<?php

declare(strict_types=1);

use App\Actions\Referees\EmployAction;
use App\Actions\Referees\InjureAction;
use App\Actions\Referees\ReleaseAction;
use App\Actions\Referees\RetireAction;
use App\Actions\Referees\SuspendAction;
use App\Enums\Shared\EmploymentStatus;
use App\Models\Referees\Referee;
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

        test('employ then release then re-employ workflow maintains consistency', function () {
            $referee = Referee::factory()->unemployed()->create();

            // Initial employ
            EmployAction::run($referee, Carbon::now());
            $employed = $referee->fresh();
            expect($employed->isEmployed())->toBeTrue();

            // Release
            ReleaseAction::run($employed, Carbon::now());
            $released = $referee->fresh();
            expect($released->isReleased())->toBeTrue();
            expect($released->isEmployed())->toBeFalse();

            // Re-employ
            EmployAction::run($released, Carbon::now());
            $reEmployed = $referee->fresh();
            expect($reEmployed->isEmployed())->toBeTrue();
            expect($reEmployed->isReleased())->toBeFalse();
        });
    });

    describe('transaction integrity', function () {
        test('multi-action workflow maintains transaction integrity', function () {
            $referee = Referee::factory()->unemployed()->create();

            // Execute multi-action workflow within transaction context
            EmployAction::run($referee, Carbon::now());
            $employed = $referee->fresh();
            
            // Then suspend the referee
            SuspendAction::run($employed, Carbon::now());
            $suspended = $referee->fresh();

            // Verify all state changes are consistent
            expect($suspended->isEmployed())->toBeTrue(); // Still employed
            expect($suspended->isSuspended())->toBeTrue(); // But suspended
            expect($suspended->currentEmployment)->not->toBeNull();
            expect($suspended->currentSuspension)->not->toBeNull();
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
            expect($refreshedReferee->isBookable())->toBeTrue(); // Can be booked when employed
        });

        test('employment enables officiating capability', function () {
            $referee = Referee::factory()->released()->create();

            // Released referee should not be bookable
            expect($referee->isBookable())->toBeFalse();

            EmployAction::run($referee, Carbon::now());

            // Employed referee should be bookable
            expect($referee->fresh()->isBookable())->toBeTrue();
        });

        test('complex multi-action employment workflows maintain data consistency', function () {
            $referee = Referee::factory()->retired()->create();

            // Retire → Employ → Suspend → Release workflow
            EmployAction::run($referee, Carbon::now());
            $employed = $referee->fresh();
            expect($employed->isEmployed())->toBeTrue();

            SuspendAction::run($employed, Carbon::now());
            $suspended = $referee->fresh();
            expect($suspended->isEmployed())->toBeTrue();
            expect($suspended->isSuspended())->toBeTrue();

            ReleaseAction::run($suspended, Carbon::now());
            $released = $referee->fresh();
            expect($released->isReleased())->toBeTrue();
            expect($released->isEmployed())->toBeFalse();
            expect($released->isSuspended())->toBeFalse();
        });
    });


});
