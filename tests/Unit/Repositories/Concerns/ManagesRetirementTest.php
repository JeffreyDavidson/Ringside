<?php

declare(strict_types=1);

use App\Enums\Shared\EmploymentStatus;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\WrestlerRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Unit tests for ManagesRetirement trait.
 *
 * UNIT TEST SCOPE:
 * - Repository method behavior in isolation
 * - Status field synchronization with retirement relationships
 * - Retirement record creation and management
 * - Data consistency verification
 */
describe('ManagesRetirement Trait', function () {

    beforeEach(function () {
        $this->repository = new WrestlerRepository();
        $this->wrestler = Wrestler::factory()->bookable()->create();
    });

    describe('createRetirement method', function () {
        test('creates retirement relationship record', function () {
            $retirementDate = Carbon::now();
            $initialRetirementCount = $this->wrestler->retirements()->count();

            DB::transaction(function () use ($retirementDate) {
                $this->repository->createRetirement($this->wrestler, $retirementDate);
            });

            expect($this->wrestler->fresh()->retirements()->count())
                ->toBe($initialRetirementCount + 1);
        });

        test('updates status field to retired', function () {
            $retirementDate = Carbon::now();

            // Verify wrestler starts as employed
            expect($this->wrestler->fresh()->isEmployed())->toBeTrue();
            expect($this->wrestler->fresh()->status)->toBe(EmploymentStatus::Employed);

            DB::transaction(function () use ($retirementDate) {
                $this->repository->createRetirement($this->wrestler, $retirementDate);
            });

            // Verify status is synchronized
            expect($this->wrestler->fresh()->isRetired())->toBeTrue();
            expect($this->wrestler->fresh()->status)->toBe(EmploymentStatus::Retired);
        });

        test('sets retirement start date correctly', function () {
            $retirementDate = Carbon::parse('2024-12-25 14:30:00');

            DB::transaction(function () use ($retirementDate) {
                $this->repository->createRetirement($this->wrestler, $retirementDate);
            });

            $currentRetirement = $this->wrestler->fresh()->currentRetirement()->first();
            expect($currentRetirement)->not->toBeNull();
            expect($currentRetirement->started_at->toDateTimeString())
                ->toBe($retirementDate->toDateTimeString());
        });

        test('maintains data consistency in transaction', function () {
            $retirementDate = Carbon::now();

            // Test that both operations succeed together
            DB::transaction(function () use ($retirementDate) {
                $this->repository->createRetirement($this->wrestler, $retirementDate);

                // Verify both changes are present within transaction
                expect($this->wrestler->fresh()->isRetired())->toBeTrue();
                expect($this->wrestler->fresh()->status)->toBe(EmploymentStatus::Retired);
            });

            // Verify consistency after transaction commits
            $refreshedWrestler = $this->wrestler->fresh();
            expect($refreshedWrestler->isRetired())->toBeTrue();
            expect($refreshedWrestler->status)->toBe(EmploymentStatus::Retired);
            expect($refreshedWrestler->currentRetirement()->first())->not->toBeNull();
        });

        test('handles multiple retirements correctly', function () {
            $firstRetirement = Carbon::now()->subYears(2);
            $comebackDate = Carbon::now()->subYears(1);
            $secondRetirement = Carbon::now();

            // First retirement
            DB::transaction(function () use ($firstRetirement) {
                $this->repository->createRetirement($this->wrestler, $firstRetirement);
            });

            // Comeback (end first retirement)
            DB::transaction(function () use ($comebackDate) {
                $this->repository->endRetirement($this->wrestler, $comebackDate);
            });

            // Second retirement
            DB::transaction(function () use ($secondRetirement) {
                $this->repository->createRetirement($this->wrestler, $secondRetirement);
            });

            $finalWrestler = $this->wrestler->fresh();
            expect($finalWrestler->retirements()->count())->toBe(2);
            expect($finalWrestler->isRetired())->toBeTrue();
            expect($finalWrestler->status)->toBe(EmploymentStatus::Retired);
            expect($finalWrestler->currentRetirement()->first()->started_at->toDateTimeString())
                ->toBe($secondRetirement->toDateTimeString());
        });
    });

    describe('endRetirement method', function () {
        test('ends current retirement relationship', function () {
            $wrestler = Wrestler::factory()->retired()->create();
            $comebackDate = Carbon::now();

            // Verify wrestler has active retirement
            expect($wrestler->currentRetirement())->not->toBeNull();
            expect($wrestler->currentRetirement()->first()->ended_at)->toBeNull();

            DB::transaction(function () use ($wrestler, $comebackDate) {
                $this->repository->endRetirement($wrestler, $comebackDate);
            });

            // Verify retirement is ended
            expect($wrestler->fresh()->currentRetirement()->first())->toBeNull();
        });

        test('updates status field to released after comeback', function () {
            $wrestler = Wrestler::factory()->retired()->create();
            $comebackDate = Carbon::now();

            expect($wrestler->isRetired())->toBeTrue();
            expect($wrestler->status)->toBe(EmploymentStatus::Retired);

            DB::transaction(function () use ($wrestler, $comebackDate) {
                $this->repository->endRetirement($wrestler, $comebackDate);
            });

            $refreshedWrestler = $wrestler->fresh();
            expect($refreshedWrestler->isReleased())->toBeTrue();
            expect($refreshedWrestler->status)->toBe(EmploymentStatus::Released);
            // Note: isRetired() will return false because no active retirement exists
        });

        test('sets retirement end date correctly', function () {
            $wrestler = Wrestler::factory()->retired()->create();
            $comebackDate = Carbon::parse('2024-07-04 10:00:00');

            $currentRetirement = $wrestler->currentRetirement()->first();
            expect($currentRetirement->ended_at)->toBeNull();

            DB::transaction(function () use ($wrestler, $comebackDate) {
                $this->repository->endRetirement($wrestler, $comebackDate);
            });

            $endedRetirement = $wrestler->fresh()->retirements()->whereNotNull('ended_at')->first();
            expect($endedRetirement)->not->toBeNull();
            expect($endedRetirement->ended_at->toDateTimeString())
                ->toBe($comebackDate->toDateTimeString());
        });

        test('does nothing when no active retirement exists', function () {
            $wrestler = Wrestler::factory()->bookable()->create();
            $comebackDate = Carbon::now();

            // Verify no active retirement
            expect($wrestler->currentRetirement()->first())->toBeNull();
            expect($wrestler->status)->toBe(EmploymentStatus::Employed);

            DB::transaction(function () use ($wrestler, $comebackDate) {
                $this->repository->endRetirement($wrestler, $comebackDate);
            });

            // Status should remain unchanged
            expect($wrestler->fresh()->status)->toBe(EmploymentStatus::Employed);
            expect($wrestler->fresh()->currentRetirement()->first())->toBeNull();
        });

        test('maintains data consistency in transaction', function () {
            $wrestler = Wrestler::factory()->retired()->create();
            $comebackDate = Carbon::now();

            DB::transaction(function () use ($wrestler, $comebackDate) {
                $this->repository->endRetirement($wrestler, $comebackDate);

                // Verify changes are present within transaction
                $refreshed = $wrestler->fresh();
                expect($refreshed->currentRetirement()->first())->toBeNull();
                expect($refreshed->status)->toBe(EmploymentStatus::Released);
            });

            // Verify consistency after transaction commits
            $finalWrestler = $wrestler->fresh();
            expect($finalWrestler->currentRetirement()->first())->toBeNull();
            expect($finalWrestler->status)->toBe(EmploymentStatus::Released);
            expect($finalWrestler->retirements()->whereNotNull('ended_at')->exists())->toBeTrue();
        });
    });

    describe('status field synchronization', function () {
        test('synchronizes status from employed to retired', function () {
            $wrestler = Wrestler::factory()->bookable()->create();

            expect($wrestler->status)->toBe(EmploymentStatus::Employed);
            expect($wrestler->isRetired())->toBeFalse();

            DB::transaction(function () use ($wrestler) {
                $this->repository->createRetirement($wrestler, Carbon::now());
            });

            $refreshedWrestler = $wrestler->fresh();
            expect($refreshedWrestler->status)->toBe(EmploymentStatus::Retired);
            expect($refreshedWrestler->isRetired())->toBeTrue();
        });

        test('synchronizes status from retired to released on comeback', function () {
            $wrestler = Wrestler::factory()->retired()->create();

            expect($wrestler->status)->toBe(EmploymentStatus::Retired);
            expect($wrestler->isRetired())->toBeTrue();

            DB::transaction(function () use ($wrestler) {
                $this->repository->endRetirement($wrestler, Carbon::now());
            });

            $refreshedWrestler = $wrestler->fresh();
            expect($refreshedWrestler->status)->toBe(EmploymentStatus::Released);
            expect($refreshedWrestler->isRetired())->toBeFalse();
        });
    });

    describe('business rule compliance', function () {
        test('retirement from any employment status updates to retired', function () {
            $employedWrestler = Wrestler::factory()->bookable()->create();
            $releasedWrestler = Wrestler::factory()->released()->create();
            $unemployedWrestler = Wrestler::factory()->unemployed()->create();

            // Test from employed
            DB::transaction(function () use ($employedWrestler) {
                $this->repository->createRetirement($employedWrestler, Carbon::now());
            });
            expect($employedWrestler->fresh()->status)->toBe(EmploymentStatus::Retired);

            // Test from released
            DB::transaction(function () use ($releasedWrestler) {
                $this->repository->createRetirement($releasedWrestler, Carbon::now());
            });
            expect($releasedWrestler->fresh()->status)->toBe(EmploymentStatus::Retired);

            // Test from unemployed
            DB::transaction(function () use ($unemployedWrestler) {
                $this->repository->createRetirement($unemployedWrestler, Carbon::now());
            });
            expect($unemployedWrestler->fresh()->status)->toBe(EmploymentStatus::Retired);
        });

        test('comeback always results in released status requiring re-employment', function () {
            // This reflects the business rule that comebacks require re-employment
            $wrestler = Wrestler::factory()->retired()->create();

            DB::transaction(function () use ($wrestler) {
                $this->repository->endRetirement($wrestler, Carbon::now());
            });

            $refreshedWrestler = $wrestler->fresh();
            expect($refreshedWrestler->status)->toBe(EmploymentStatus::Released);
            expect($refreshedWrestler->isBookable())->toBeFalse(); // Released wrestlers can't be booked
        });
    });

    describe('method integration', function () {
        test('retire then comeback then re-employ workflow', function () {
            $wrestler = Wrestler::factory()->bookable()->create();
            $retireDate = Carbon::now()->subDays(30);
            $comebackDate = Carbon::now()->subDays(10);
            $reEmployDate = Carbon::now();

            // Initial state: Employed
            expect($wrestler->status)->toBe(EmploymentStatus::Employed);

            // Step 1: Retire
            DB::transaction(function () use ($wrestler, $retireDate) {
                $this->repository->createRetirement($wrestler, $retireDate);
            });
            expect($wrestler->fresh()->status)->toBe(EmploymentStatus::Retired);

            // Step 2: Comeback (end retirement)
            DB::transaction(function () use ($wrestler, $comebackDate) {
                $this->repository->endRetirement($wrestler, $comebackDate);
            });
            expect($wrestler->fresh()->status)->toBe(EmploymentStatus::Released);

            // Step 3: Re-employ (requires employment action)
            DB::transaction(function () use ($wrestler, $reEmployDate) {
                $this->repository->createEmployment($wrestler, $reEmployDate);
            });

            $finalWrestler = $wrestler->fresh();
            expect($finalWrestler->status)->toBe(EmploymentStatus::Employed);
            expect($finalWrestler->isEmployed())->toBeTrue();
            expect($finalWrestler->isRetired())->toBeFalse();
            expect($finalWrestler->isBookable())->toBeTrue();
        });
    });
});
