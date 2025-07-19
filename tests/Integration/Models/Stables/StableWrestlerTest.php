<?php

declare(strict_types=1);

use App\Models\Stables\Stable;
use App\Models\Stables\StableWrestler;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;

/**
 * Integration tests for StableWrestler pivot model functionality.
 *
 * This test suite validates the complete workflow of stable-wrestler relationships
 * including wrestlers joining stables, leaving stables, querying current and previous
 * memberships, and ensuring proper business rule enforcement.
 *
 * Tests cover the wrestler-specific stable membership functionality with real database
 * relationships using the stables_wrestlers pivot table.
 *
 * @see \App\Models\Stables\StableWrestler
 */
describe('StableWrestler Pivot Model', function () {
    beforeEach(function () {
        // Create test entities with realistic factory states
        $this->stable = Stable::factory()->unactivated()->create([
            'name' => 'The Four Horsemen',
        ]);

        $this->wrestler = Wrestler::factory()->employed()->create([
            'name' => 'Ric Flair',
            'hometown' => 'Charlotte, North Carolina',
        ]);

        $this->secondStable = Stable::factory()->unactivated()->create([
            'name' => 'D-Generation X',
        ]);

        $this->secondWrestler = Wrestler::factory()->employed()->create([
            'name' => 'Tully Blanchard',
            'hometown' => 'San Antonio, Texas',
        ]);
    });

    describe('Wrestler-Stable Membership Creation', function () {
        test('wrestler can join a stable with proper pivot data', function () {
            $joinedDate = Carbon::now()->subMonths(6);

            // Create the relationship using the attach method with pivot data
            $this->wrestler->stables()->attach($this->stable->id, [
                'joined_at' => $joinedDate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Verify the relationship exists
            expect($this->wrestler->stables()->count())->toBe(1);
            expect($this->wrestler->currentStable)->not()->toBeNull();
            expect($this->wrestler->previousStables()->count())->toBe(0);

            // Verify pivot data is correct
            $pivotData = $this->wrestler->stables()->first()->pivot;
            expect(Carbon::parse($pivotData->joined_at)->format('Y-m-d H:i:s'))->toBe($joinedDate->format('Y-m-d H:i:s'));
            expect($pivotData->left_at)->toBeNull();
            expect($pivotData->wrestler_id)->toBe($this->wrestler->id);
            expect($pivotData->stable_id)->toBe($this->stable->id);
        });

        test('stable can have multiple wrestlers', function () {
            $wrestlerJoinDate = Carbon::now()->subMonths(6);
            $secondWrestlerJoinDate = Carbon::now()->subMonths(4);

            // Attach different wrestlers to the same stable
            $this->wrestler->stables()->attach($this->stable->id, [
                'joined_at' => $wrestlerJoinDate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->secondWrestler->stables()->attach($this->stable->id, [
                'joined_at' => $secondWrestlerJoinDate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Verify all relationships exist
            expect($this->wrestler->currentStable->id)->toBe($this->stable->id);
            expect($this->secondWrestler->currentStable->id)->toBe($this->stable->id);

            // Verify stable has both wrestlers
            expect($this->stable->currentWrestlers()->count())->toBe(2);

            // Verify total pivot record count
            $wrestlerCount = StableWrestler::where('stable_id', $this->stable->id)
                ->whereNull('left_at')
                ->count();
            expect($wrestlerCount)->toBe(2);
        });

        test('wrestler can be part of multiple stables across different time periods', function () {
            $firstPeriodStart = Carbon::now()->subYear();
            $firstPeriodEnd = Carbon::now()->subMonths(6);
            $secondPeriodStart = Carbon::now()->subMonths(3);

            // First stable membership (completed)
            $this->wrestler->stables()->attach($this->stable->id, [
                'joined_at' => $firstPeriodStart,
                'left_at' => $firstPeriodEnd,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Second stable membership (current)
            $this->wrestler->stables()->attach($this->secondStable->id, [
                'joined_at' => $secondPeriodStart,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Verify relationship counts
            expect($this->wrestler->stables()->count())->toBe(2);
            expect($this->wrestler->currentStable)->not()->toBeNull();
            expect($this->wrestler->previousStables()->count())->toBe(1);

            // Verify current stable is correct
            $currentStable = $this->wrestler->currentStable;
            expect($currentStable->id)->toBe($this->secondStable->id);
            expect(Carbon::parse($currentStable->pivot->joined_at)->format('Y-m-d H:i:s'))->toBe($secondPeriodStart->format('Y-m-d H:i:s'));
            expect($currentStable->pivot->left_at)->toBeNull();

            // Verify previous stable is correct
            $previousStable = $this->wrestler->previousStables()->first();
            expect($previousStable->id)->toBe($this->stable->id);
            expect(Carbon::parse($previousStable->pivot->joined_at)->format('Y-m-d H:i:s'))->toBe($firstPeriodStart->format('Y-m-d H:i:s'));
            expect(Carbon::parse($previousStable->pivot->left_at)->format('Y-m-d H:i:s'))->toBe($firstPeriodEnd->format('Y-m-d H:i:s'));
        });
    });

    describe('Wrestler-Stable Membership Termination', function () {
        beforeEach(function () {
            // Set up active stable membership
            $this->wrestler->stables()->attach($this->stable->id, [
                'joined_at' => Carbon::now()->subMonths(6),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        test('wrestler leaving stable updates pivot correctly', function () {
            $leaveDate = Carbon::now();

            // End the relationship by updating the pivot
            $this->wrestler->stables()->updateExistingPivot($this->stable->id, [
                'left_at' => $leaveDate,
                'updated_at' => now(),
            ]);

            // Verify relationship status changed
            expect($this->wrestler->currentStable)->toBeNull();
            expect($this->wrestler->previousStables()->count())->toBe(1);

            // Verify pivot data is updated
            $previousStable = $this->wrestler->previousStables()->first();
            expect(Carbon::parse($previousStable->pivot->left_at)->format('Y-m-d H:i:s'))->toBe($leaveDate->format('Y-m-d H:i:s'));
        });

        test('detaching wrestler completely removes relationship', function () {
            // Detach the wrestler from stable
            $this->wrestler->stables()->detach($this->stable->id);

            // Verify all relationships are gone
            expect($this->wrestler->stables()->count())->toBe(0);
            expect($this->wrestler->currentStable)->toBeNull();
            expect($this->wrestler->previousStables()->count())->toBe(0);

            // Verify pivot record is deleted
            expect(StableWrestler::where('wrestler_id', $this->wrestler->id)
                ->where('stable_id', $this->stable->id)
                ->exists())->toBeFalse();
        });
    });

    describe('StableWrestler Pivot Model Direct Queries', function () {
        test('StableWrestler pivot model can be queried directly', function () {
            $this->wrestler->stables()->attach($this->stable->id, [
                'joined_at' => Carbon::now()->subMonths(6),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $pivotRecord = StableWrestler::where('wrestler_id', $this->wrestler->id)
                ->where('stable_id', $this->stable->id)
                ->first();

            expect($pivotRecord)->not()->toBeNull();
            expect($pivotRecord->wrestler_id)->toBe($this->wrestler->id);
            expect($pivotRecord->stable_id)->toBe($this->stable->id);
            expect($pivotRecord->joined_at)->toBeInstanceOf(Carbon::class);
            expect($pivotRecord->left_at)->toBeNull();

            // Test pivot relationships
            expect($pivotRecord->wrestler->id)->toBe($this->wrestler->id);
            expect($pivotRecord->stable->id)->toBe($this->stable->id);
        });

        test('pivot model handles date casting correctly', function () {
            $joinedDate = Carbon::now()->subMonths(6);
            $leftDate = Carbon::now()->subMonths(1);

            // Test wrestler pivot
            $this->wrestler->stables()->attach($this->stable->id, [
                'joined_at' => $joinedDate,
                'left_at' => $leftDate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $wrestlerPivot = StableWrestler::where('wrestler_id', $this->wrestler->id)
                ->where('stable_id', $this->stable->id)
                ->first();

            expect($wrestlerPivot->joined_at)->toBeInstanceOf(Carbon::class);
            expect($wrestlerPivot->left_at)->toBeInstanceOf(Carbon::class);
            expect($wrestlerPivot->joined_at->format('Y-m-d H:i:s'))->toBe($joinedDate->format('Y-m-d H:i:s'));
            expect($wrestlerPivot->left_at->format('Y-m-d H:i:s'))->toBe($leftDate->format('Y-m-d H:i:s'));
        });
    });

    describe('Wrestler Stable Queries', function () {
        beforeEach(function () {
            // Set up complex membership scenario
            $this->wrestler->stables()->attach($this->stable->id, [
                'joined_at' => Carbon::now()->subYear(),
                'left_at' => Carbon::now()->subMonths(6),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->wrestler->stables()->attach($this->secondStable->id, [
                'joined_at' => Carbon::now()->subMonths(3),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        test('current stable query returns only active relationship', function () {
            $currentStable = $this->wrestler->currentStable;

            expect($currentStable)->not()->toBeNull();
            expect($currentStable->id)->toBe($this->secondStable->id);
            expect($currentStable->pivot->left_at)->toBeNull();
        });

        test('previous stables query returns only completed relationships', function () {
            $previousStables = $this->wrestler->previousStables()->get();

            expect($previousStables)->toHaveCount(1);
            expect($previousStables->first()->id)->toBe($this->stable->id);
            expect($previousStables->first()->pivot->left_at)->not()->toBeNull();
        });

        test('all stables query returns complete membership history', function () {
            $allStables = $this->wrestler->stables()->get();

            expect($allStables)->toHaveCount(2);

            $stableIds = $allStables->pluck('id')->toArray();
            expect($stableIds)->toContain($this->stable->id);
            expect($stableIds)->toContain($this->secondStable->id);
        });

        test('isNotCurrentlyInStable method works correctly', function () {
            // Wrestler is currently in secondStable, not in stable
            expect($this->wrestler->isNotCurrentlyInStable($this->stable))->toBeTrue();
            expect($this->wrestler->isNotCurrentlyInStable($this->secondStable))->toBeFalse();
        });
    });
});