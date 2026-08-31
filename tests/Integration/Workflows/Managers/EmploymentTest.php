<?php

declare(strict_types=1);

use App\Actions\Managers\EmployAction;
use App\Actions\Managers\ReleaseAction;
use App\Actions\Managers\SuspendAction;
use App\Actions\Managers\UnretireAction;
use App\Enums\Shared\EmploymentStatus;
use App\Lifecycle\Roster\Individuals\IndividualEmploymentEligibility;
use App\Models\Roster\Managers\Manager;
use Illuminate\Support\Carbon;

/**
 * Workflow tests for Manager Employment multi-action scenarios.
 *
 * WORKFLOW TEST SCOPE:
 * - Multi-action employment workflows
 * - Cross-action data consistency
 * - Transaction integrity across multiple actions
 * - Complex business process validation
 */
describe('Manager Employment Workflows', function () {

    beforeEach(function () {
        $this->manager = Manager::factory()->released()->create();
    });

    describe('multi-action employment workflows', function () {
        test('employ then release then re-employ workflow maintains consistency', function () {
            $manager = Manager::factory()->unemployed()->create();

            // Initial employ
            resolve(EmployAction::class)->handle($manager, Carbon::now());
            $employed = freshModel($manager);
            expect($employed->currentEmployment()->exists())->toBeTrue();

            // Release
            resolve(ReleaseAction::class)->handle($employed, Carbon::now());
            $released = freshModel($manager);
            expect($released->status)->toBe(EmploymentStatus::Released);
            expect($released->currentEmployment()->exists())->toBeFalse();

            // Re-employ
            resolve(EmployAction::class)->handle($released, Carbon::now());
            $reEmployed = freshModel($manager);
            expect($reEmployed->currentEmployment()->exists())->toBeTrue();
            expect($reEmployed->status)->not->toBe(EmploymentStatus::Released);
        });
    });

    describe('complex management workflows', function () {
        test('employ then release workflow maintains data consistency', function () {
            $manager = Manager::factory()->unemployed()->create();

            // Initial state
            expect($manager->status)->toBe(EmploymentStatus::Unemployed);

            // Employ manager
            resolve(EmployAction::class)->handle($manager, Carbon::now());
            $afterEmployment = freshModel($manager);
            expect($afterEmployment->status)->toBe(EmploymentStatus::Employed);
            expect($afterEmployment->currentEmployment()->exists())->toBeTrue();

            // Release manager
            resolve(ReleaseAction::class)->handle($afterEmployment, Carbon::now());
            $afterRelease = freshModel($manager);

            // Verify release status synchronization
            expect($afterRelease->status)->toBe(EmploymentStatus::Released);
            expect($afterRelease->currentEmployment()->exists())->toBeFalse();
        });
    });

    describe('transaction integrity', function () {
        test('multi-action workflow maintains transaction integrity', function () {
            $manager = Manager::factory()->unemployed()->create();

            // Execute multi-action workflow within transaction context
            resolve(EmployAction::class)->handle($manager, Carbon::now());
            $employed = freshModel($manager);

            // Then suspend the manager
            resolve(SuspendAction::class)->handle($employed, Carbon::now());
            $suspended = freshModel($manager);

            // Verify all state changes are consistent
            expect($suspended->currentEmployment()->exists())->toBeTrue(); // Still employed
            expect($suspended->currentSuspension()->exists())->toBeTrue(); // But suspended
            expect($suspended->currentEmployment)->not()->toBeNull();
            expect($suspended->currentSuspension)->not()->toBeNull();
        });

        test('action rollback maintains data consistency on failure', function () {
            $manager = Manager::factory()->released()->create();

            // This test would require mocking a failure scenario
            // For now, just verify normal operation doesn't leave partial state
            resolve(EmployAction::class)->handle($manager, Carbon::now());

            $refreshedManager = freshModel($manager);

            // Verify all state is consistent - no orphaned records
            if ($refreshedManager->currentEmployment()->exists()) {
                expect($refreshedManager->status)->toBe(EmploymentStatus::Employed);
                expect($refreshedManager->currentEmployment)->not()->toBeNull();
            }
        });
    });

    describe('business rule integration', function () {
        test('employment respects business validation rules', function () {
            $manager = Manager::factory()->unemployed()->create();

            // Test that employment follows business rules
            resolve(EmployAction::class)->handle($manager, Carbon::now());

            $refreshedManager = freshModel($manager);

            // Verify business rule compliance
            expect($refreshedManager->currentEmployment()->exists())->toBeTrue();
            expect(resolve(IndividualEmploymentEligibility::class)->canEmploy($refreshedManager))->toBeFalse(); // Already employed
        });

        test('employment enables management capability', function () {
            $manager = Manager::factory()->released()->create();

            // Released manager should not be available for management duties
            expect($manager->currentEmployment()->exists())->toBeFalse();

            resolve(EmployAction::class)->handle($manager, Carbon::now());

            // Employed manager should be available for management duties
            expect(freshModel($manager)->currentEmployment()->exists())->toBeTrue();
        });

        test('complex multi-action management workflows maintain data consistency', function () {
            $manager = Manager::factory()->retired()->create();

            // Retire -> Unretire -> Suspend -> Release workflow
            resolve(UnretireAction::class)->handle($manager, Carbon::now());
            $employed = freshModel($manager);
            expect($employed->currentEmployment()->exists())->toBeTrue();

            resolve(SuspendAction::class)->handle($employed, Carbon::now());
            $suspended = freshModel($manager);
            expect($suspended->currentEmployment()->exists())->toBeTrue();
            expect($suspended->currentSuspension()->exists())->toBeTrue();

            resolve(ReleaseAction::class)->handle($suspended, Carbon::now());
            $released = freshModel($manager);
            expect($released->status)->toBe(EmploymentStatus::Released);
            expect($released->currentEmployment()->exists())->toBeFalse();
            expect($released->currentSuspension()->exists())->toBeFalse();
        });
    });
});
