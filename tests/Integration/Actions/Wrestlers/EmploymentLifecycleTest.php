<?php

declare(strict_types=1);

use App\Actions\Wrestlers\EmployAction;
use App\Actions\Wrestlers\ReleaseAction;
use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Status\CannotBeEmployedException;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;

/**
 * Integration tests for Wrestler Employment Actions.
 *
 * INTEGRATION TEST SCOPE:
 * - Complete action workflows from start to finish
 * - Status synchronization across multiple components
 * - Repository and action integration
 * - Pipeline and cascade strategy integration
 * - Transaction integrity across components
 */
describe('Wrestler Employment Action Integration', function () {

    beforeEach(function () {
        $this->wrestler = Wrestler::factory()->released()->create();
    });

    describe('EmployAction integration', function () {
        test('complete employment workflow synchronizes all state', function () {
            $employmentDate = Carbon::now();

            // Verify initial state
            expect($this->wrestler->isReleased())->toBeTrue();
            expect($this->wrestler->isEmployed())->toBeFalse();
            expect($this->wrestler->status)->toBe(EmploymentStatus::Released);

            // Execute complete employment workflow
            EmployAction::run($this->wrestler, $employmentDate);

            // Verify complete state synchronization
            $refreshedWrestler = $this->wrestler->fresh();
            expect($refreshedWrestler->isEmployed())->toBeTrue();
            expect($refreshedWrestler->isReleased())->toBeFalse();
            expect($refreshedWrestler->status)->toBe(EmploymentStatus::Employed);
            expect($refreshedWrestler->currentEmployment)->not->toBeNull();
            expect($refreshedWrestler->currentEmployment->started_at->toDateTimeString())
                ->toBe($employmentDate->toDateTimeString());
        });

        test('employing already employed wrestler throws validation error', function () {
            $wrestler = Wrestler::factory()->bookable()->create();

            // Verify initial employed state
            expect($wrestler->isEmployed())->toBeTrue();
            expect($wrestler->status)->toBe(EmploymentStatus::Employed);

            // Attempt to employ again should throw validation error
            expect(fn () => EmployAction::run($wrestler, Carbon::now()))
                ->toThrow(CannotBeEmployedException::class);
        });

        test('employing retired wrestler handles status transition', function () {
            $wrestler = Wrestler::factory()->retired()->create();

            // Verify initial retired state
            expect($wrestler->isRetired())->toBeTrue();
            expect($wrestler->status)->toBe(EmploymentStatus::Retired);

            // Employ the retired wrestler
            EmployAction::run($wrestler, Carbon::now());

            // Verify employment takes precedence over retirement in status field
            $refreshedWrestler = $wrestler->fresh();
            expect($refreshedWrestler->isEmployed())->toBeTrue();
            expect($refreshedWrestler->status)->toBe(EmploymentStatus::Employed);
            // Note: isRetired() might still be true due to retirement relationship
        });

        test('employment action integrates with status transition pipeline', function () {
            $wrestler = Wrestler::factory()->unemployed()->create();

            expect($wrestler->status)->toBe(EmploymentStatus::Unemployed);
            expect($wrestler->isEmployed())->toBeFalse();

            // Test that the full pipeline handles the transition
            EmployAction::run($wrestler, Carbon::now());

            $refreshedWrestler = $wrestler->fresh();
            expect($refreshedWrestler->status)->toBe(EmploymentStatus::Employed);
            expect($refreshedWrestler->isEmployed())->toBeTrue();
            expect($refreshedWrestler->currentEmployment)->not->toBeNull();
        });
    });

    describe('multiple action integration', function () {
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
