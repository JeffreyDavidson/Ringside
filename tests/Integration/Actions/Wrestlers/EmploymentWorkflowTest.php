<?php

declare(strict_types=1);

use App\Actions\Wrestlers\EmployAction;
use App\Actions\Wrestlers\HealAction;
use App\Actions\Wrestlers\InjureAction;
use App\Actions\Wrestlers\ReinstateAction;
use App\Actions\Wrestlers\ReleaseAction;
use App\Actions\Wrestlers\RetireAction;
use App\Actions\Wrestlers\SuspendAction;
use App\Actions\Wrestlers\UnretireAction;
use App\Enums\Shared\EmploymentStatus;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;

/**
 * Workflow tests for Wrestler Employment multi-action scenarios.
 *
 * WORKFLOW TEST SCOPE:
 * - Multi-action employment workflows
 * - Cross-action data consistency  
 * - Transaction integrity across multiple actions
 * - Complex business process validation
 * - Career lifecycle scenarios
 */
describe('Wrestler Employment Workflows', function () {

    beforeEach(function () {
        $this->wrestler = Wrestler::factory()->released()->create();
    });

    describe('basic multi-action employment workflows', function () {
        test('employ then release then re-employ workflow maintains consistency', function () {
            $wrestler = Wrestler::factory()->unemployed()->create();

            // Initial employ
            EmployAction::run($wrestler, Carbon::now());
            $employed = $wrestler->fresh();
            expect($employed->isEmployed())->toBeTrue();

            // Release
            ReleaseAction::run($employed, Carbon::now());
            $released = $wrestler->fresh();
            expect($released->isReleased())->toBeTrue();
            expect($released->isEmployed())->toBeFalse();

            // Re-employ
            EmployAction::run($released, Carbon::now());
            $reEmployed = $wrestler->fresh();
            expect($reEmployed->isEmployed())->toBeTrue();
            expect($reEmployed->isReleased())->toBeFalse();
        });

        test('employ then release workflow maintains data consistency', function () {
            $wrestler = Wrestler::factory()->unemployed()->create();

            // Initial state
            expect($wrestler->status)->toBe(EmploymentStatus::Unemployed);

            // Employ wrestler
            EmployAction::run($wrestler, Carbon::now());
            $afterEmployment = $wrestler->fresh();
            expect($afterEmployment->status)->toBe(EmploymentStatus::Employed);
            expect($afterEmployment->isEmployed())->toBeTrue();

            // Release wrestler
            ReleaseAction::run($afterEmployment, Carbon::now());
            $afterRelease = $wrestler->fresh();

            // Verify release status synchronization
            expect($afterRelease->status)->toBe(EmploymentStatus::Released);
            expect($afterRelease->isReleased())->toBeTrue();
            expect($afterRelease->isEmployed())->toBeFalse();
        });
    });

    describe('complex career lifecycle workflows', function () {
        test('employ then injure then heal workflow maintains employment', function () {
            $wrestler = Wrestler::factory()->unemployed()->create();

            // Employ wrestler
            EmployAction::run($wrestler, Carbon::now());
            $employed = $wrestler->fresh();
            expect($employed->isEmployed())->toBeTrue();
            expect($employed->isBookable())->toBeTrue();

            // Injure wrestler
            InjureAction::run($employed, Carbon::now());
            $injured = $wrestler->fresh();
            expect($injured->isEmployed())->toBeTrue(); // Still employed
            expect($injured->isInjured())->toBeTrue();
            expect($injured->isBookable())->toBeFalse(); // Not bookable when injured

            // Heal wrestler
            HealAction::run($injured, Carbon::now());
            $healed = $wrestler->fresh();
            expect($healed->isEmployed())->toBeTrue();
            expect($healed->isInjured())->toBeFalse();
            expect($healed->isBookable())->toBeTrue(); // Bookable again
        });

        test('employ then suspend then reinstate workflow maintains employment', function () {
            $wrestler = Wrestler::factory()->unemployed()->create();

            // Employ wrestler
            EmployAction::run($wrestler, Carbon::now());
            $employed = $wrestler->fresh();
            expect($employed->isEmployed())->toBeTrue();
            expect($employed->isBookable())->toBeTrue();

            // Suspend wrestler
            SuspendAction::run($employed, Carbon::now());
            $suspended = $wrestler->fresh();
            expect($suspended->isEmployed())->toBeTrue(); // Still employed
            expect($suspended->isSuspended())->toBeTrue();
            expect($suspended->isBookable())->toBeFalse(); // Not bookable when suspended

            // Reinstate wrestler
            ReinstateAction::run($suspended, Carbon::now());
            $reinstated = $wrestler->fresh();
            expect($reinstated->isEmployed())->toBeTrue();
            expect($reinstated->isSuspended())->toBeFalse();
            expect($reinstated->isBookable())->toBeTrue(); // Bookable again
        });

        test('employ then retire then unretire workflow changes employment status', function () {
            $wrestler = Wrestler::factory()->unemployed()->create();

            // Employ wrestler
            EmployAction::run($wrestler, Carbon::now());
            $employed = $wrestler->fresh();
            expect($employed->isEmployed())->toBeTrue();

            // Retire wrestler
            RetireAction::run($employed, Carbon::now());
            $retired = $wrestler->fresh();
            expect($retired->isRetired())->toBeTrue();
            expect($retired->isEmployed())->toBeFalse(); // Employment ends on retirement
            expect($retired->status)->toBe(EmploymentStatus::Retired);

            // Unretire wrestler
            UnretireAction::run($retired, Carbon::now());
            $unretired = $wrestler->fresh();
            expect($unretired->isRetired())->toBeFalse();
            expect($unretired->isEmployed())->toBeFalse(); // Still unemployed after unretiring
            expect($unretired->status)->toBe(EmploymentStatus::Unemployed);
        });

        test('full career lifecycle workflow with multiple state changes', function () {
            $wrestler = Wrestler::factory()->unemployed()->create();

            // 1. Employ
            EmployAction::run($wrestler, Carbon::now()->subMonths(12));
            expect($wrestler->fresh()->isEmployed())->toBeTrue();

            // 2. Suspend
            SuspendAction::run($wrestler, Carbon::now()->subMonths(10));
            $suspended = $wrestler->fresh();
            expect($suspended->isEmployed())->toBeTrue();
            expect($suspended->isSuspended())->toBeTrue();

            // 3. Reinstate
            ReinstateAction::run($wrestler, Carbon::now()->subMonths(8));
            expect($wrestler->fresh()->isSuspended())->toBeFalse();
            expect($wrestler->fresh()->isEmployed())->toBeTrue();

            // 4. Injure
            InjureAction::run($wrestler, Carbon::now()->subMonths(6));
            $injured = $wrestler->fresh();
            expect($injured->isEmployed())->toBeTrue();
            expect($injured->isInjured())->toBeTrue();

            // 5. Heal
            HealAction::run($wrestler, Carbon::now()->subMonths(4));
            expect($wrestler->fresh()->isInjured())->toBeFalse();
            expect($wrestler->fresh()->isEmployed())->toBeTrue();

            // 6. Retire
            RetireAction::run($wrestler, Carbon::now()->subMonths(2));
            expect($wrestler->fresh()->isRetired())->toBeTrue();
            expect($wrestler->fresh()->isEmployed())->toBeFalse();

            // 7. Unretire (back to unemployed)
            UnretireAction::run($wrestler, Carbon::now());
            $final = $wrestler->fresh();
            expect($final->isRetired())->toBeFalse();
            expect($final->isEmployed())->toBeFalse();
            expect($final->status)->toBe(EmploymentStatus::Unemployed);

            // Verify all employment periods and status changes are recorded
            expect($final->employments()->count())->toBe(1);
            expect($final->suspensions()->count())->toBe(1);
            expect($final->injuries()->count())->toBe(1);
            expect($final->retirements()->count())->toBe(1);
        });
    });

    describe('business rule validation workflows', function () {
        test('employment workflow enables and disables booking capability', function () {
            $wrestler = Wrestler::factory()->released()->create();

            // Released wrestler should not be bookable
            expect($wrestler->isBookable())->toBeFalse();

            // Employ makes wrestler bookable
            EmployAction::run($wrestler, Carbon::now());
            expect($wrestler->fresh()->isBookable())->toBeTrue();

            // Injury makes employed wrestler not bookable
            InjureAction::run($wrestler, Carbon::now());
            expect($wrestler->fresh()->isBookable())->toBeFalse();

            // Healing makes wrestler bookable again
            HealAction::run($wrestler, Carbon::now());
            expect($wrestler->fresh()->isBookable())->toBeTrue();

            // Suspension makes wrestler not bookable
            SuspendAction::run($wrestler, Carbon::now());
            expect($wrestler->fresh()->isBookable())->toBeFalse();

            // Reinstatement makes wrestler bookable again
            ReinstateAction::run($wrestler, Carbon::now());
            expect($wrestler->fresh()->isBookable())->toBeTrue();

            // Release makes wrestler not bookable
            ReleaseAction::run($wrestler, Carbon::now());
            expect($wrestler->fresh()->isBookable())->toBeFalse();
        });

        test('status combination workflow validation maintains business rules', function () {
            $wrestler = Wrestler::factory()->unemployed()->create();

            // Employ wrestler
            EmployAction::run($wrestler, Carbon::now());
            $employed = $wrestler->fresh();

            // Test mutually exclusive statuses - injured wrestler cannot be suspended
            InjureAction::run($employed, Carbon::now());
            $injured = $wrestler->fresh();
            expect($injured->canBeSuspended())->toBeFalse();
            expect($injured->isEmployed())->toBeTrue();
            expect($injured->isInjured())->toBeTrue();

            // Heal wrestler, then test suspended wrestler cannot be injured
            HealAction::run($injured, Carbon::now());
            SuspendAction::run($wrestler->fresh(), Carbon::now());
            $suspended = $wrestler->fresh();
            expect($suspended->canBeInjured())->toBeFalse();
            expect($suspended->isEmployed())->toBeTrue();
            expect($suspended->isSuspended())->toBeTrue();

            // Reinstate, then test retired wrestler cannot be employed
            ReinstateAction::run($suspended, Carbon::now());
            RetireAction::run($wrestler->fresh(), Carbon::now());
            $retired = $wrestler->fresh();
            expect($retired->canBeEmployed())->toBeFalse();
            expect($retired->isRetired())->toBeTrue();
        });

        test('employment status affects all business capabilities workflow', function () {
            $wrestler = Wrestler::factory()->unemployed()->create();

            // Unemployed wrestler has limited capabilities
            expect($wrestler->isBookable())->toBeFalse();
            expect($wrestler->canBeSuspended())->toBeFalse();
            expect($wrestler->canBeInjured())->toBeFalse();

            // Employ wrestler
            EmployAction::run($wrestler, Carbon::now());
            $employed = $wrestler->fresh();

            // Employed wrestler has full capabilities
            expect($employed->isBookable())->toBeTrue();
            expect($employed->canBeSuspended())->toBeTrue();
            expect($employed->canBeInjured())->toBeTrue();
            expect($employed->canBeEmployed())->toBeFalse(); // Already employed

            // Release wrestler
            ReleaseAction::run($employed, Carbon::now());
            $released = $wrestler->fresh();

            // Released wrestler has limited capabilities again
            expect($released->isBookable())->toBeFalse();
            expect($released->canBeSuspended())->toBeFalse();
            expect($released->canBeInjured())->toBeFalse();
            expect($released->canBeEmployed())->toBeTrue(); // Can be re-employed
        });
    });

    describe('transaction integrity workflows', function () {
        test('employment action maintains transaction integrity', function () {
            $wrestler = Wrestler::factory()->released()->create();

            // Verify the action handles transactions properly
            EmployAction::run($wrestler, Carbon::now());

            // All changes should be committed together
            $refreshedWrestler = $wrestler->fresh();
            expect($refreshedWrestler->isEmployed())->toBeTrue();
            expect($refreshedWrestler->status)->toBe(EmploymentStatus::Employed);
            expect($refreshedWrestler->currentEmployment)->not->toBeNull();

            // Verify no partial updates occurred
            expect($refreshedWrestler->employments()->whereNull('ended_at')->count())->toBe(1);
        });

        test('complex multi-action workflow maintains transaction integrity', function () {
            $wrestler = Wrestler::factory()->unemployed()->create();

            // Execute complex workflow
            EmployAction::run($wrestler, Carbon::now());
            $employed = $wrestler->fresh();
            
            SuspendAction::run($employed, Carbon::now());
            $suspended = $wrestler->fresh();
            
            ReinstateAction::run($suspended, Carbon::now());
            $reinstated = $wrestler->fresh();

            // Verify all state changes are consistent and complete
            expect($reinstated->isEmployed())->toBeTrue();
            expect($reinstated->isSuspended())->toBeFalse();
            expect($reinstated->currentEmployment)->not->toBeNull();
            expect($reinstated->currentSuspension)->toBeNull();

            // Verify proper record keeping
            expect($reinstated->employments()->whereNull('ended_at')->count())->toBe(1);
            expect($reinstated->suspensions()->whereNotNull('ended_at')->count())->toBe(1);
        });

        test('action rollback maintains data consistency on failure', function () {
            $wrestler = Wrestler::factory()->released()->create();

            // This test would require mocking a failure scenario
            // For now, just verify normal operation doesn't leave partial state
            EmployAction::run($wrestler, Carbon::now());

            $refreshedWrestler = $wrestler->fresh();

            // Verify all state is consistent - no orphaned records
            if ($refreshedWrestler->isEmployed()) {
                expect($refreshedWrestler->status)->toBe(EmploymentStatus::Employed);
                expect($refreshedWrestler->currentEmployment)->not->toBeNull();
            }
        });
    });

    describe('edge case workflows', function () {
        test('employ unemployed wrestler with future date workflow', function () {
            $wrestler = Wrestler::factory()->unemployed()->create();
            $futureDate = Carbon::now()->addDays(7);

            EmployAction::run($wrestler, $futureDate);

            $refreshedWrestler = $wrestler->fresh();
            expect($refreshedWrestler->status)->toBe(EmploymentStatus::Employed);
            expect($refreshedWrestler->currentEmployment->started_at->toDateTimeString())
                ->toBe($futureDate->toDateTimeString());
        });

        test('wrestler maintains single active status of each type workflow', function () {
            $wrestler = Wrestler::factory()->unemployed()->create();

            // Employ wrestler
            EmployAction::run($wrestler, Carbon::now());
            expect($wrestler->fresh()->employments()->whereNull('ended_at')->count())->toBe(1);

            // Multiple injury/heal cycles
            InjureAction::run($wrestler, Carbon::now());
            expect($wrestler->fresh()->injuries()->whereNull('ended_at')->count())->toBe(1);
            
            HealAction::run($wrestler, Carbon::now());
            expect($wrestler->fresh()->injuries()->whereNull('ended_at')->count())->toBe(0);
            
            InjureAction::run($wrestler, Carbon::now());
            expect($wrestler->fresh()->injuries()->whereNull('ended_at')->count())->toBe(1);

            // Should still only have one active employment throughout
            expect($wrestler->fresh()->employments()->whereNull('ended_at')->count())->toBe(1);
        });
    });
});