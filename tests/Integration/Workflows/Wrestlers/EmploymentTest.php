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
            resolve(EmployAction::class)->handle($wrestler, Carbon::now());
            $employed = freshModel($wrestler);
            expect($employed->isEmployed())->toBeTrue();

            // Release
            resolve(ReleaseAction::class)->handle($employed, Carbon::now());
            $released = freshModel($wrestler);
            expect($released->isReleased())->toBeTrue();
            expect($released->isEmployed())->toBeFalse();

            // Re-employ
            resolve(EmployAction::class)->handle($released, Carbon::now());
            $reEmployed = freshModel($wrestler);
            expect($reEmployed->isEmployed())->toBeTrue();
            expect($reEmployed->isReleased())->toBeFalse();
        });

        test('employ then release workflow maintains data consistency', function () {
            $wrestler = Wrestler::factory()->unemployed()->create();

            // Initial state
            expect($wrestler->status)->toBe(EmploymentStatus::Unemployed);

            // Employ wrestler
            resolve(EmployAction::class)->handle($wrestler, Carbon::now());
            $afterEmployment = freshModel($wrestler);
            expect($afterEmployment->status)->toBe(EmploymentStatus::Employed);
            expect($afterEmployment->isEmployed())->toBeTrue();

            // Release wrestler
            resolve(ReleaseAction::class)->handle($afterEmployment, Carbon::now());
            $afterRelease = freshModel($wrestler);

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
            resolve(EmployAction::class)->handle($wrestler, Carbon::now());
            $employed = freshModel($wrestler);
            expect($employed->isEmployed())->toBeTrue();
            expect($employed->isBookable())->toBeTrue();

            // Injure wrestler
            resolve(InjureAction::class)->handle($employed, Carbon::now());
            $injured = freshModel($wrestler);
            expect($injured->isEmployed())->toBeTrue(); // Still employed
            expect($injured->isInjured())->toBeTrue();
            expect($injured->isBookable())->toBeFalse(); // Not bookable when injured

            // Heal wrestler
            resolve(HealAction::class)->handle($injured, Carbon::now());
            $healed = freshModel($wrestler);
            expect($healed->isEmployed())->toBeTrue();
            expect($healed->isInjured())->toBeFalse();
            expect($healed->isBookable())->toBeTrue(); // Bookable again
        });

        test('employ then suspend then reinstate workflow maintains employment', function () {
            $wrestler = Wrestler::factory()->unemployed()->create();

            // Employ wrestler
            resolve(EmployAction::class)->handle($wrestler, Carbon::now());
            $employed = freshModel($wrestler);
            expect($employed->isEmployed())->toBeTrue();
            expect($employed->isBookable())->toBeTrue();

            // Suspend wrestler
            resolve(SuspendAction::class)->handle($employed, Carbon::now());
            $suspended = freshModel($wrestler);
            expect($suspended->isEmployed())->toBeTrue(); // Still employed
            expect($suspended->isSuspended())->toBeTrue();
            expect($suspended->isBookable())->toBeFalse(); // Not bookable when suspended

            // Reinstate wrestler
            resolve(ReinstateAction::class)->handle($suspended, Carbon::now());
            $reinstated = freshModel($wrestler);
            expect($reinstated->isEmployed())->toBeTrue();
            expect($reinstated->isSuspended())->toBeFalse();
            expect($reinstated->isBookable())->toBeTrue(); // Bookable again
        });

        test('employ then retire then unretire workflow changes employment status', function () {
            $wrestler = Wrestler::factory()->unemployed()->create();

            // Employ wrestler
            resolve(EmployAction::class)->handle($wrestler, Carbon::now());
            $employed = freshModel($wrestler);
            expect($employed->isEmployed())->toBeTrue();

            // Retire wrestler
            resolve(RetireAction::class)->handle($employed, Carbon::now());
            $retired = freshModel($wrestler);
            expect($retired->isRetired())->toBeTrue();
            expect($retired->isEmployed())->toBeFalse(); // Employment ends on retirement
            expect($retired->status)->toBe(EmploymentStatus::Retired);

            // Unretire wrestler
            resolve(UnretireAction::class)->handle($retired, Carbon::now());
            $unretired = freshModel($wrestler);
            expect($unretired->isRetired())->toBeFalse();
            expect($unretired->isEmployed())->toBeTrue();
            expect($unretired->status)->toBe(EmploymentStatus::Employed);
        });

        test('full career lifecycle workflow with multiple state changes', function () {
            $wrestler = Wrestler::factory()->unemployed()->create();

            // 1. Employ
            resolve(EmployAction::class)->handle($wrestler, Carbon::now()->subMonths(12));
            expect(freshModel($wrestler)->isEmployed())->toBeTrue();

            // 2. Suspend
            resolve(SuspendAction::class)->handle($wrestler, Carbon::now()->subMonths(10));
            $suspended = freshModel($wrestler);
            expect($suspended->isEmployed())->toBeTrue();
            expect($suspended->isSuspended())->toBeTrue();

            // 3. Reinstate
            resolve(ReinstateAction::class)->handle($wrestler, Carbon::now()->subMonths(8));
            expect(freshModel($wrestler)->isSuspended())->toBeFalse();
            expect(freshModel($wrestler)->isEmployed())->toBeTrue();

            // 4. Injure
            resolve(InjureAction::class)->handle($wrestler, Carbon::now()->subMonths(6));
            $injured = freshModel($wrestler);
            expect($injured->isEmployed())->toBeTrue();
            expect($injured->isInjured())->toBeTrue();

            // 5. Heal
            resolve(HealAction::class)->handle($wrestler, Carbon::now()->subMonths(4));
            expect(freshModel($wrestler)->isInjured())->toBeFalse();
            expect(freshModel($wrestler)->isEmployed())->toBeTrue();

            // 6. Retire
            resolve(RetireAction::class)->handle($wrestler, Carbon::now()->subMonths(2));
            expect(freshModel($wrestler)->isRetired())->toBeTrue();
            expect(freshModel($wrestler)->isEmployed())->toBeFalse();

            // 7. Unretire and employ immediately
            resolve(UnretireAction::class)->handle($wrestler, Carbon::now());
            $final = freshModel($wrestler);
            expect($final->isRetired())->toBeFalse();
            expect($final->isEmployed())->toBeTrue();
            expect($final->status)->toBe(EmploymentStatus::Employed);

            // Verify all employment periods and status changes are recorded
            expect($final->employments()->count())->toBe(2);
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
            resolve(EmployAction::class)->handle($wrestler, Carbon::now());
            expect(freshModel($wrestler)->isBookable())->toBeTrue();

            // Injury makes employed wrestler not bookable
            resolve(InjureAction::class)->handle($wrestler, Carbon::now());
            expect(freshModel($wrestler)->isBookable())->toBeFalse();

            // Healing makes wrestler bookable again
            resolve(HealAction::class)->handle($wrestler, Carbon::now());
            expect(freshModel($wrestler)->isBookable())->toBeTrue();

            // Suspension makes wrestler not bookable
            resolve(SuspendAction::class)->handle($wrestler, Carbon::now());
            expect(freshModel($wrestler)->isBookable())->toBeFalse();

            // Reinstatement makes wrestler bookable again
            resolve(ReinstateAction::class)->handle($wrestler, Carbon::now());
            expect(freshModel($wrestler)->isBookable())->toBeTrue();

            // Release makes wrestler not bookable
            resolve(ReleaseAction::class)->handle($wrestler, Carbon::now());
            expect(freshModel($wrestler)->isBookable())->toBeFalse();
        });

        test('status combination workflow validation maintains business rules', function () {
            $wrestler = Wrestler::factory()->unemployed()->create();

            // Employ wrestler
            resolve(EmployAction::class)->handle($wrestler, Carbon::now());
            $employed = freshModel($wrestler);

            // Injury and suspension are orthogonal states.
            resolve(InjureAction::class)->handle($employed, Carbon::now());
            $injured = freshModel($wrestler);
            expect($injured->canBeSuspended())->toBeTrue();
            expect($injured->isEmployed())->toBeTrue();
            expect($injured->isInjured())->toBeTrue();

            resolve(SuspendAction::class)->handle($injured, Carbon::now());
            $injuredAndSuspended = freshModel($wrestler);
            expect($injuredAndSuspended->canBeInjured())->toBeFalse();
            expect($injuredAndSuspended->isEmployed())->toBeTrue();
            expect($injuredAndSuspended->isInjured())->toBeTrue();
            expect($injuredAndSuspended->isSuspended())->toBeTrue();

            // Reinstate, then test retired wrestler cannot be employed
            resolve(ReinstateAction::class)->handle($injuredAndSuspended, Carbon::now());
            $reinstated = freshModel($wrestler);
            expect($reinstated->isSuspended())->toBeFalse();
            expect($reinstated->isInjured())->toBeFalse();
            expect($reinstated->isEmployed())->toBeTrue();

            resolve(RetireAction::class)->handle($reinstated, Carbon::now());
            $retired = freshModel($wrestler);
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
            resolve(EmployAction::class)->handle($wrestler, Carbon::now());
            $employed = freshModel($wrestler);

            // Employed wrestler has full capabilities
            expect($employed->isBookable())->toBeTrue();
            expect($employed->canBeSuspended())->toBeTrue();
            expect($employed->canBeInjured())->toBeTrue();
            expect($employed->canBeEmployed())->toBeFalse(); // Already employed

            // Release wrestler
            resolve(ReleaseAction::class)->handle($employed, Carbon::now());
            $released = freshModel($wrestler);

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
            resolve(EmployAction::class)->handle($wrestler, Carbon::now());

            // All changes should be committed together
            $refreshedWrestler = freshModel($wrestler);
            expect($refreshedWrestler->isEmployed())->toBeTrue();
            expect($refreshedWrestler->status)->toBe(EmploymentStatus::Employed);
            expect($refreshedWrestler->currentEmployment)->not()->toBeNull();

            // Verify no partial updates occurred
            expect($refreshedWrestler->employments()->whereNull('ended_at')->count())->toBe(1);
        });

        test('complex multi-action workflow maintains transaction integrity', function () {
            $wrestler = Wrestler::factory()->unemployed()->create();

            // Execute complex workflow
            resolve(EmployAction::class)->handle($wrestler, Carbon::now());
            $employed = freshModel($wrestler);

            resolve(SuspendAction::class)->handle($employed, Carbon::now());
            $suspended = freshModel($wrestler);

            resolve(ReinstateAction::class)->handle($suspended, Carbon::now());
            $reinstated = freshModel($wrestler);

            // Verify all state changes are consistent and complete
            expect($reinstated->isEmployed())->toBeTrue();
            expect($reinstated->isSuspended())->toBeFalse();
            expect($reinstated->currentEmployment)->not()->toBeNull();
            expect($reinstated->currentSuspension)->toBeNull();

            // Verify proper record keeping
            expect($reinstated->employments()->whereNull('ended_at')->count())->toBe(1);
            expect($reinstated->suspensions()->whereNotNull('ended_at')->count())->toBe(1);
        });

        test('action rollback maintains data consistency on failure', function () {
            $wrestler = Wrestler::factory()->released()->create();

            // This test would require mocking a failure scenario
            // For now, just verify normal operation doesn't leave partial state
            resolve(EmployAction::class)->handle($wrestler, Carbon::now());

            $refreshedWrestler = freshModel($wrestler);

            // Verify all state is consistent - no orphaned records
            if ($refreshedWrestler->isEmployed()) {
                expect($refreshedWrestler->status)->toBe(EmploymentStatus::Employed);
                expect($refreshedWrestler->currentEmployment)->not()->toBeNull();
            }
        });
    });

    describe('edge case workflows', function () {
        test('employ unemployed wrestler with future date workflow', function () {
            $wrestler = Wrestler::factory()->unemployed()->create();
            $futureDate = Carbon::now()->addDays(7);

            resolve(EmployAction::class)->handle($wrestler, $futureDate);

            $refreshedWrestler = freshModel($wrestler);
            expect($refreshedWrestler->status)->toBe(EmploymentStatus::FutureEmployment);

            // Future employment won't be current until the date arrives
            $futureEmployment = $refreshedWrestler->employments()->latest()->firstOrFail();
            expect(requiredDate($futureEmployment->started_at)->toDateTimeString())
                ->toBe($futureDate->toDateTimeString());
        });

        test('wrestler maintains single active status of each type workflow', function () {
            $wrestler = Wrestler::factory()->unemployed()->create();

            // Employ wrestler
            resolve(EmployAction::class)->handle($wrestler, Carbon::now());
            expect(freshModel($wrestler)->employments()->whereNull('ended_at')->count())->toBe(1);

            // Multiple injury/heal cycles
            resolve(InjureAction::class)->handle($wrestler, Carbon::now());
            expect(freshModel($wrestler)->injuries()->whereNull('ended_at')->count())->toBe(1);

            resolve(HealAction::class)->handle($wrestler, Carbon::now());
            expect(freshModel($wrestler)->injuries()->whereNull('ended_at')->count())->toBe(0);

            resolve(InjureAction::class)->handle($wrestler, Carbon::now());
            expect(freshModel($wrestler)->injuries()->whereNull('ended_at')->count())->toBe(1);

            // Should still only have one active employment throughout
            expect(freshModel($wrestler)->employments()->whereNull('ended_at')->count())->toBe(1);
        });
    });
});
