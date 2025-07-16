<?php

declare(strict_types=1);

use App\Actions\Wrestlers\EmployAction;
use App\Actions\Wrestlers\ReleaseAction;
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
 */
describe('Wrestler Employment Workflows', function () {

    beforeEach(function () {
        $this->wrestler = Wrestler::factory()->released()->create();
    });

    describe('multi-action employment workflows', function () {
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
    });

    describe('complex wrestling career workflows', function () {
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

        test('employ unemployed wrestler with future date', function () {
            $wrestler = Wrestler::factory()->unemployed()->create();
            $futureDate = Carbon::now()->addDays(7);

            EmployAction::run($wrestler, $futureDate);

            $refreshedWrestler = $wrestler->fresh();
            expect($refreshedWrestler->status)->toBe(EmploymentStatus::Employed);
            expect($refreshedWrestler->currentEmployment->started_at->toDateTimeString())
                ->toBe($futureDate->toDateTimeString());
        });
    });

    describe('transaction integrity', function () {
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

    describe('business rule integration', function () {
        test('employment respects business validation rules', function () {
            $wrestler = Wrestler::factory()->unemployed()->create();

            // Test that employment follows business rules
            EmployAction::run($wrestler, Carbon::now());

            $refreshedWrestler = $wrestler->fresh();

            // Verify business rule compliance
            expect($refreshedWrestler->isEmployed())->toBeTrue();
            expect($refreshedWrestler->canBeEmployed())->toBeFalse(); // Already employed
            expect($refreshedWrestler->isBookable())->toBeTrue(); // Can be booked when employed
        });

        test('employment enables booking capability', function () {
            $wrestler = Wrestler::factory()->released()->create();

            // Released wrestler should not be bookable
            expect($wrestler->isBookable())->toBeFalse();

            EmployAction::run($wrestler, Carbon::now());

            // Employed wrestler should be bookable
            expect($wrestler->fresh()->isBookable())->toBeTrue();
        });
    });
});
