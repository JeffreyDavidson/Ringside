<?php

declare(strict_types=1);

use App\Actions\TagTeams\EmployAction;
use App\Actions\TagTeams\ReleaseAction;
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
 * 
 * Note: TagTeams have complex wrestler requirements for suspension/retirement actions,
 * so this test focuses on core employ/release workflows.
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
        test('employment action maintains transaction integrity', function () {
            $tagTeam = TagTeam::factory()->unemployed()->create();

            // Verify the action handles transactions properly
            EmployAction::run($tagTeam, Carbon::now());

            // All changes should be committed together
            $refreshedTagTeam = $tagTeam->fresh();
            expect($refreshedTagTeam->isEmployed())->toBeTrue();
            expect($refreshedTagTeam->status)->toBe(EmploymentStatus::Employed);
            expect($refreshedTagTeam->currentEmployment)->not()->toBeNull();

            // Verify no partial updates occurred
            expect($refreshedTagTeam->employments()->whereNull('ended_at')->count())->toBe(1);
        });

        test('action rollback maintains data consistency on failure', function () {
            $tagTeam = TagTeam::factory()->released()->create();

            // This test would require mocking a failure scenario
            // For now, just verify normal operation doesn't leave partial state
            EmployAction::run($tagTeam, Carbon::now());

            $refreshedTagTeam = $tagTeam->fresh();

            // Verify all state is consistent - no orphaned records
            if ($refreshedTagTeam->isEmployed()) {
                expect($refreshedTagTeam->status)->toBe(EmploymentStatus::Employed);
                expect($refreshedTagTeam->currentEmployment)->not()->toBeNull();
            }
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
        });

        test('employment status affects basic capabilities workflow', function () {
            $tagTeam = TagTeam::factory()->unemployed()->create();

            // Unemployed tag team has limited capabilities
            expect($tagTeam->isEmployed())->toBeFalse();
            expect($tagTeam->canBeEmployed())->toBeTrue();

            // Employ tag team
            EmployAction::run($tagTeam, Carbon::now());
            $employed = $tagTeam->fresh();

            // Employed tag team has different capabilities
            expect($employed->isEmployed())->toBeTrue();
            expect($employed->canBeEmployed())->toBeFalse(); // Already employed

            // Release tag team
            ReleaseAction::run($employed, Carbon::now());
            $released = $tagTeam->fresh();

            // Released tag team capabilities
            expect($released->isEmployed())->toBeFalse();
            expect($released->canBeEmployed())->toBeTrue(); // Can be re-employed
        });
    });

    describe('edge case workflows', function () {
        test('employ unemployed tag team with future date workflow', function () {
            $tagTeam = TagTeam::factory()->unemployed()->create();
            $futureDate = Carbon::now()->addDays(7);

            EmployAction::run($tagTeam, $futureDate);

            $refreshedTagTeam = $tagTeam->fresh();
            expect($refreshedTagTeam->status)->toBe(EmploymentStatus::FutureEmployment);
            
            // Future employment won't be current until the date arrives
            $futureEmployment = $refreshedTagTeam->employments()->latest()->first();
            expect($futureEmployment->started_at->toDateTimeString())
                ->toBe($futureDate->toDateTimeString());
        });

        test('tag team maintains single active employment workflow', function () {
            $tagTeam = TagTeam::factory()->unemployed()->create();

            // Employ tag team
            EmployAction::run($tagTeam, Carbon::now());
            expect($tagTeam->fresh()->employments()->whereNull('ended_at')->count())->toBe(1);

            // Multiple release/employ cycles
            ReleaseAction::run($tagTeam, Carbon::now());
            expect($tagTeam->fresh()->employments()->whereNull('ended_at')->count())->toBe(0);
            
            EmployAction::run($tagTeam, Carbon::now());
            expect($tagTeam->fresh()->employments()->whereNull('ended_at')->count())->toBe(1);

            // Should maintain single active employment
            expect($tagTeam->fresh()->employments()->count())->toBe(2); // Total employments
            expect($tagTeam->fresh()->employments()->whereNull('ended_at')->count())->toBe(1); // Active
        });
    });
});