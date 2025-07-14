<?php

declare(strict_types=1);

use App\Enums\Shared\EmploymentStatus;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\WrestlerRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Unit tests for ManagesEmployment trait.
 *
 * UNIT TEST SCOPE:
 * - Repository method behavior in isolation
 * - Status field synchronization with relationships
 * - Employment record creation and management
 * - Data consistency verification
 */
describe('ManagesEmployment Trait', function () {

    beforeEach(function () {
        $this->repository = new WrestlerRepository();
        $this->wrestler = Wrestler::factory()->released()->create();
    });

    describe('createEmployment method', function () {
        test('creates employment relationship record', function () {
            $employmentDate = Carbon::now();
            $initialEmploymentCount = $this->wrestler->employments()->count();

            DB::transaction(function () use ($employmentDate) {
                $this->repository->createEmployment($this->wrestler, $employmentDate);
            });

            expect($this->wrestler->fresh()->employments()->count())
                ->toBe($initialEmploymentCount + 1);
        });

        test('updates status field to employed', function () {
            $employmentDate = Carbon::now();

            // Verify wrestler starts as released
            expect($this->wrestler->fresh()->isReleased())->toBeTrue();
            expect($this->wrestler->fresh()->isEmployed())->toBeFalse();

            DB::transaction(function () use ($employmentDate) {
                $this->repository->createEmployment($this->wrestler, $employmentDate);
            });

            // Verify status is synchronized
            expect($this->wrestler->fresh()->isEmployed())->toBeTrue();
            expect($this->wrestler->fresh()->isReleased())->toBeFalse();
            expect($this->wrestler->fresh()->status)->toBe(EmploymentStatus::Employed);
        });

        test('sets employment start date correctly', function () {
            $employmentDate = Carbon::parse('2024-01-15 10:00:00');

            DB::transaction(function () use ($employmentDate) {
                $this->repository->createEmployment($this->wrestler, $employmentDate);
            });

            $currentEmployment = $this->wrestler->fresh()->currentEmployment;
            expect($currentEmployment)->not->toBeNull();
            expect($currentEmployment->started_at->toDateTimeString())
                ->toBe($employmentDate->toDateTimeString());
        });

        test('ends previous employment when creating new one', function () {
            $firstDate = Carbon::now()->subDays(10);
            $secondDate = Carbon::now();

            // Create first employment
            DB::transaction(function () use ($firstDate) {
                $this->repository->createEmployment($this->wrestler, $firstDate);
            });

            $firstEmployment = $this->wrestler->fresh()->currentEmployment;
            expect($firstEmployment->ended_at)->toBeNull();

            // Create second employment (should end first)
            DB::transaction(function () use ($secondDate) {
                $this->repository->createEmployment($this->wrestler, $secondDate);
            });

            // Verify first employment is ended and second is active
            expect($this->wrestler->fresh()->employments()->count())->toBe(2);
            expect($this->wrestler->fresh()->currentEmployment->started_at->toDateTimeString())
                ->toBe($secondDate->toDateTimeString());
        });

        test('maintains data consistency in transaction', function () {
            $employmentDate = Carbon::now();

            // Test that both operations succeed or fail together
            DB::transaction(function () use ($employmentDate) {
                $this->repository->createEmployment($this->wrestler, $employmentDate);

                // Verify both changes are present within transaction
                expect($this->wrestler->fresh()->isEmployed())->toBeTrue();
                expect($this->wrestler->fresh()->status)->toBe(EmploymentStatus::Employed);
            });

            // Verify consistency after transaction commits
            $refreshedWrestler = $this->wrestler->fresh();
            expect($refreshedWrestler->isEmployed())->toBeTrue();
            expect($refreshedWrestler->status)->toBe(EmploymentStatus::Employed);
            expect($refreshedWrestler->currentEmployment)->not->toBeNull();
        });
    });

    describe('status field synchronization', function () {
        test('synchronizes status from unemployed to employed', function () {
            $wrestler = Wrestler::factory()->unemployed()->create();

            expect($wrestler->status)->toBe(EmploymentStatus::Unemployed);

            DB::transaction(function () use ($wrestler) {
                $this->repository->createEmployment($wrestler, Carbon::now());
            });

            expect($wrestler->fresh()->status)->toBe(EmploymentStatus::Employed);
        });

        test('synchronizes status from released to employed', function () {
            $wrestler = Wrestler::factory()->released()->create();

            expect($wrestler->status)->toBe(EmploymentStatus::Released);

            DB::transaction(function () use ($wrestler) {
                $this->repository->createEmployment($wrestler, Carbon::now());
            });

            expect($wrestler->fresh()->status)->toBe(EmploymentStatus::Employed);
        });

        test('maintains employed status when already employed', function () {
            $wrestler = Wrestler::factory()->bookable()->create();

            expect($wrestler->status)->toBe(EmploymentStatus::Employed);

            DB::transaction(function () use ($wrestler) {
                $this->repository->createEmployment($wrestler, Carbon::now());
            });

            expect($wrestler->fresh()->status)->toBe(EmploymentStatus::Employed);
        });
    });

    describe('business rule compliance', function () {
        test('employing retired wrestler requires ending retirement first', function () {
            $wrestler = Wrestler::factory()->retired()->create();

            expect($wrestler->status)->toBe(EmploymentStatus::Retired);
            expect($wrestler->isRetired())->toBeTrue();

            // First end the retirement (comeback)
            DB::transaction(function () use ($wrestler) {
                $this->repository->endRetirement($wrestler, Carbon::now()->subDays(1));
            });

            // Verify wrestler is no longer retired but is released
            expect($wrestler->fresh()->isRetired())->toBeFalse();
            expect($wrestler->fresh()->status)->toBe(EmploymentStatus::Released);

            // Now employment should be possible
            DB::transaction(function () use ($wrestler) {
                $this->repository->createEmployment($wrestler, Carbon::now());
            });

            // Status field should be updated to Employed
            expect($wrestler->fresh()->status)->toBe(EmploymentStatus::Employed);
            expect($wrestler->fresh()->isEmployed())->toBeTrue();
        });
    });

    describe('endEmployment method', function () {
        test('ends current employment relationship', function () {
            $wrestler = Wrestler::factory()->bookable()->create();
            $endDate = Carbon::now();

            // Verify wrestler has active employment
            expect($wrestler->currentEmployment)->not->toBeNull();
            expect($wrestler->currentEmployment->ended_at)->toBeNull();

            DB::transaction(function () use ($wrestler, $endDate) {
                $this->repository->endEmployment($wrestler, $endDate);
            });

            // Verify employment is ended
            expect($wrestler->fresh()->currentEmployment)->toBeNull();
        });

        test('updates status field to released', function () {
            $wrestler = Wrestler::factory()->bookable()->create();
            $endDate = Carbon::now();

            expect($wrestler->isEmployed())->toBeTrue();
            expect($wrestler->status)->toBe(EmploymentStatus::Employed);

            DB::transaction(function () use ($wrestler, $endDate) {
                $this->repository->endEmployment($wrestler, $endDate);
            });

            $refreshedWrestler = $wrestler->fresh();
            expect($refreshedWrestler->isReleased())->toBeTrue();
            expect($refreshedWrestler->isEmployed())->toBeFalse();
            expect($refreshedWrestler->status)->toBe(EmploymentStatus::Released);
        });

        test('sets employment end date correctly', function () {
            $wrestler = Wrestler::factory()->bookable()->create();
            $endDate = Carbon::parse('2024-12-31 15:30:00');

            $currentEmployment = $wrestler->currentEmployment;
            expect($currentEmployment->ended_at)->toBeNull();

            DB::transaction(function () use ($wrestler, $endDate) {
                $this->repository->endEmployment($wrestler, $endDate);
            });

            $endedEmployment = $wrestler->fresh()->employments()->whereNotNull('ended_at')->first();
            expect($endedEmployment)->not->toBeNull();
            expect($endedEmployment->ended_at->toDateTimeString())
                ->toBe($endDate->toDateTimeString());
        });

        test('does nothing when no active employment exists', function () {
            $wrestler = Wrestler::factory()->released()->create();
            $endDate = Carbon::now();

            // Verify no active employment
            expect($wrestler->currentEmployment)->toBeNull();
            expect($wrestler->status)->toBe(EmploymentStatus::Released);

            DB::transaction(function () use ($wrestler, $endDate) {
                $this->repository->endEmployment($wrestler, $endDate);
            });

            // Status should remain unchanged
            expect($wrestler->fresh()->status)->toBe(EmploymentStatus::Released);
            expect($wrestler->fresh()->currentEmployment)->toBeNull();
        });
    });

    describe('createRelease method', function () {
        test('creates release by ending employment', function () {
            $wrestler = Wrestler::factory()->bookable()->create();
            $releaseDate = Carbon::now();

            expect($wrestler->isEmployed())->toBeTrue();

            DB::transaction(function () use ($wrestler, $releaseDate) {
                $this->repository->createRelease($wrestler, $releaseDate);
            });

            $refreshedWrestler = $wrestler->fresh();
            expect($refreshedWrestler->isReleased())->toBeTrue();
            expect($refreshedWrestler->isEmployed())->toBeFalse();
            expect($refreshedWrestler->status)->toBe(EmploymentStatus::Released);
        });

        test('delegates to endEmployment method', function () {
            $wrestler = Wrestler::factory()->bookable()->create();
            $releaseDate = Carbon::parse('2024-06-15 12:00:00');

            $currentEmployment = $wrestler->currentEmployment;
            expect($currentEmployment->ended_at)->toBeNull();

            DB::transaction(function () use ($wrestler, $releaseDate) {
                $this->repository->createRelease($wrestler, $releaseDate);
            });

            // Verify the employment record was updated with correct end date
            $endedEmployment = $wrestler->fresh()->employments()->whereNotNull('ended_at')->first();
            expect($endedEmployment)->not->toBeNull();
            expect($endedEmployment->ended_at->toDateTimeString())
                ->toBe($releaseDate->toDateTimeString());
        });
    });

    describe('createReinstatement method', function () {
        test('creates reinstatement by employing wrestler', function () {
            $wrestler = Wrestler::factory()->released()->create();
            $reinstatementDate = Carbon::now();

            expect($wrestler->isReleased())->toBeTrue();
            expect($wrestler->isEmployed())->toBeFalse();

            DB::transaction(function () use ($wrestler, $reinstatementDate) {
                $this->repository->createReinstatement($wrestler, $reinstatementDate);
            });

            $refreshedWrestler = $wrestler->fresh();
            expect($refreshedWrestler->isEmployed())->toBeTrue();
            expect($refreshedWrestler->isReleased())->toBeFalse();
            expect($refreshedWrestler->status)->toBe(EmploymentStatus::Employed);
        });

        test('delegates to createEmployment method', function () {
            $wrestler = Wrestler::factory()->unemployed()->create();
            $reinstatementDate = Carbon::parse('2024-03-01 09:00:00');

            $initialEmploymentCount = $wrestler->employments()->count();

            DB::transaction(function () use ($wrestler, $reinstatementDate) {
                $this->repository->createReinstatement($wrestler, $reinstatementDate);
            });

            // Verify new employment record was created
            expect($wrestler->fresh()->employments()->count())
                ->toBe($initialEmploymentCount + 1);

            $currentEmployment = $wrestler->fresh()->currentEmployment;
            expect($currentEmployment)->not->toBeNull();
            expect($currentEmployment->started_at->toDateTimeString())
                ->toBe($reinstatementDate->toDateTimeString());
        });
    });

    describe('method integration', function () {
        test('employ then release then reinstate workflow', function () {
            $wrestler = Wrestler::factory()->unemployed()->create();
            $employDate = Carbon::now()->subDays(30);
            $releaseDate = Carbon::now()->subDays(10);
            $reinstateDate = Carbon::now();

            // Initial state: Unemployed
            expect($wrestler->status)->toBe(EmploymentStatus::Unemployed);

            // Step 1: Employ
            DB::transaction(function () use ($wrestler, $employDate) {
                $this->repository->createEmployment($wrestler, $employDate);
            });
            expect($wrestler->fresh()->status)->toBe(EmploymentStatus::Employed);

            // Step 2: Release
            DB::transaction(function () use ($wrestler, $releaseDate) {
                $this->repository->createRelease($wrestler, $releaseDate);
            });
            expect($wrestler->fresh()->status)->toBe(EmploymentStatus::Released);

            // Step 3: Reinstate
            DB::transaction(function () use ($wrestler, $reinstateDate) {
                $this->repository->createReinstatement($wrestler, $reinstateDate);
            });

            $finalWrestler = $wrestler->fresh();
            expect($finalWrestler->status)->toBe(EmploymentStatus::Employed);
            expect($finalWrestler->isEmployed())->toBeTrue();
            expect($finalWrestler->currentEmployment->started_at->toDateTimeString())
                ->toBe($reinstateDate->toDateTimeString());
        });
    });
});
