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
 * Workflow tests for TagTeam Employment multi-action scenarios.
 *
 * WORKFLOW TEST SCOPE:
 * - Multi-action employment workflows
 * - Cross-action data consistency
 * - Transaction integrity across multiple actions
 * - Complex business process validation
 */
describe('TagTeam Employment Workflows', function () {

    beforeEach(function () {
        $this->tagTeam = TagTeam::factory()->released()->create();
    });

    describe('multi-action employment workflows', function () {
        test('employ then release then re-employ workflow maintains consistency', function () {
            $tagTeam = TagTeam::factory()->unemployed()->create();

            // Initial employ
            EmployAction::run($tagTeam, Carbon::now());
            $employed = $tagTeam->fresh();
            expect($employed->isEmployed())->toBeTrue();

            // Release
            ReleaseAction::run($employed, Carbon::now());
            $released = $tagTeam->fresh();
            expect($released->isReleased())->toBeTrue();
            expect($released->isEmployed())->toBeFalse();

            // Re-employ
            EmployAction::run($released, Carbon::now());
            $reEmployed = $tagTeam->fresh();
            expect($reEmployed->isEmployed())->toBeTrue();
            expect($reEmployed->isReleased())->toBeFalse();
        });
    });

    describe('complex tag team workflows', function () {
        test('employ then release workflow maintains data consistency', function () {
            $tagTeam = TagTeam::factory()->unemployed()->create();

            // Initial state
            expect($tagTeam->status)->toBe(EmploymentStatus::Unemployed);

            // Employ tag team
            EmployAction::run($tagTeam, Carbon::now());
            $afterEmployment = $tagTeam->fresh();
            expect($afterEmployment->status)->toBe(EmploymentStatus::Employed);
            expect($afterEmployment->isEmployed())->toBeTrue();

            // Release tag team
            ReleaseAction::run($afterEmployment, Carbon::now());
            $afterRelease = $tagTeam->fresh();

            // Verify release status synchronization
            expect($afterRelease->status)->toBe(EmploymentStatus::Released);
            expect($afterRelease->isReleased())->toBeTrue();
            expect($afterRelease->isEmployed())->toBeFalse();
        });

        test('full employment lifecycle workflow', function () {
            $tagTeam = TagTeam::factory()->unemployed()->create();

            // Employ
            EmployAction::run($tagTeam, Carbon::now()->subYear());
            expect($tagTeam->fresh()->isEmployed())->toBeTrue();

            // Suspend
            SuspendAction::run($tagTeam, Carbon::now()->subMonths(9));
            expect($tagTeam->fresh()->isSuspended())->toBeTrue();

            // Reinstate
            ReinstateAction::run($tagTeam, Carbon::now()->subMonths(6));
            expect($tagTeam->fresh()->isEmployed())->toBeTrue();

            // Retire
            RetireAction::run($tagTeam, Carbon::now()->subMonths(3));
            expect($tagTeam->fresh()->isRetired())->toBeTrue();

            // Unretire
            UnretireAction::run($tagTeam, Carbon::now());

            $finalTagTeam = $tagTeam->fresh();
            expect($finalTagTeam->isUnemployed())->toBeTrue();

            // Verify all periods are recorded
            expect($finalTagTeam->employments()->count())->toBe(1);
            expect($finalTagTeam->suspensions()->count())->toBe(1);
            expect($finalTagTeam->retirements()->count())->toBe(1);
        });

        test('multiple employment periods with gaps workflow', function () {
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
    });

    describe('transaction integrity', function () {
        test('multi-action workflow maintains transaction integrity', function () {
            $tagTeam = TagTeam::factory()->unemployed()->create();

            // Execute multi-action workflow within transaction context
            EmployAction::run($tagTeam, Carbon::now());
            $employed = $tagTeam->fresh();
            
            // Then suspend the tag team
            SuspendAction::run($employed, Carbon::now());
            $suspended = $tagTeam->fresh();

            // Verify all state changes are consistent
            expect($suspended->isEmployed())->toBeTrue(); // Still employed
            expect($suspended->isSuspended())->toBeTrue(); // But suspended
            expect($suspended->currentEmployment)->not->toBeNull();
            expect($suspended->currentSuspension)->not->toBeNull();
        });

        test('suspension across employment boundaries workflow', function () {
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

    describe('business rule integration', function () {
        test('employment respects business validation rules', function () {
            $tagTeam = TagTeam::factory()->unemployed()->create();

            // Test that employment follows business rules
            EmployAction::run($tagTeam, Carbon::now());

            $refreshedTagTeam = $tagTeam->fresh();

            // Verify business rule compliance
            expect($refreshedTagTeam->isEmployed())->toBeTrue();
            expect($refreshedTagTeam->canBeEmployed())->toBeFalse(); // Already employed
            expect($refreshedTagTeam->isBookable())->toBeTrue(); // Can be booked when employed
        });

        test('employment enables booking capability', function () {
            $tagTeam = TagTeam::factory()->released()->create();

            // Released tag team should not be bookable
            expect($tagTeam->isBookable())->toBeFalse();

            EmployAction::run($tagTeam, Carbon::now());

            // Employed tag team should be bookable
            expect($tagTeam->fresh()->isBookable())->toBeTrue();
        });

        test('complex multi-action tag team workflows maintain data consistency', function () {
            $tagTeam = TagTeam::factory()->retired()->create();

            // Retire → Employ → Suspend → Reinstate → Release workflow
            EmployAction::run($tagTeam, Carbon::now());
            $employed = $tagTeam->fresh();
            expect($employed->isEmployed())->toBeTrue();

            SuspendAction::run($employed, Carbon::now());
            $suspended = $tagTeam->fresh();
            expect($suspended->isEmployed())->toBeTrue();
            expect($suspended->isSuspended())->toBeTrue();

            ReinstateAction::run($suspended, Carbon::now());
            $reinstated = $tagTeam->fresh();
            expect($reinstated->isEmployed())->toBeTrue();
            expect($reinstated->isSuspended())->toBeFalse();

            ReleaseAction::run($reinstated, Carbon::now());
            $released = $tagTeam->fresh();
            expect($released->isReleased())->toBeTrue();
            expect($released->isEmployed())->toBeFalse();
        });
    });
});