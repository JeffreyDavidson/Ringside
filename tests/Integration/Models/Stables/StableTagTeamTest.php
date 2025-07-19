<?php

declare(strict_types=1);

use App\Models\Stables\Stable;
use App\Models\Stables\StableTagTeam;
use App\Models\TagTeams\TagTeam;
use Illuminate\Support\Carbon;

/**
 * Integration tests for StableTagTeam pivot model functionality.
 *
 * This test suite validates the complete workflow of stable-tag team relationships
 * including tag teams joining stables, leaving stables, querying current and previous
 * memberships, and ensuring proper business rule enforcement.
 *
 * Tests cover the tag team-specific stable membership functionality with real database
 * relationships using the stables_tag_teams pivot table.
 *
 * @see \App\Models\Stables\StableTagTeam
 */
describe('StableTagTeam Pivot Model', function () {
    beforeEach(function () {
        // Create test entities with realistic factory states
        $this->stable = Stable::factory()->unactivated()->create([
            'name' => 'The Four Horsemen',
        ]);

        $this->tagTeam = TagTeam::factory()->employed()->create([
            'name' => 'The Brain Busters',
        ]);

        $this->secondStable = Stable::factory()->unactivated()->create([
            'name' => 'D-Generation X',
        ]);

        $this->secondTagTeam = TagTeam::factory()->employed()->create([
            'name' => 'The New Age Outlaws',
        ]);
    });

    describe('TagTeam-Stable Membership Creation', function () {
        test('tag team can join a stable with proper pivot data', function () {
            $joinedDate = Carbon::now()->subMonths(4);

            // Create the relationship using the attach method with pivot data
            $this->tagTeam->stables()->attach($this->stable->id, [
                'joined_at' => $joinedDate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Verify the relationship exists
            expect($this->tagTeam->stables()->count())->toBe(1);
            expect($this->tagTeam->currentStable)->not()->toBeNull();
            expect($this->tagTeam->previousStables()->count())->toBe(0);

            // Verify pivot data is correct
            $pivotData = $this->tagTeam->stables()->first()->pivot;
            expect(Carbon::parse($pivotData->joined_at)->format('Y-m-d H:i:s'))->toBe($joinedDate->format('Y-m-d H:i:s'));
            expect($pivotData->left_at)->toBeNull();
            expect($pivotData->tag_team_id)->toBe($this->tagTeam->id);
            expect($pivotData->stable_id)->toBe($this->stable->id);
        });

        test('stable can have multiple tag teams', function () {
            $tagTeamJoinDate = Carbon::now()->subMonths(6);
            $secondTagTeamJoinDate = Carbon::now()->subMonths(4);

            // Attach different tag teams to the same stable
            $this->tagTeam->stables()->attach($this->stable->id, [
                'joined_at' => $tagTeamJoinDate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->secondTagTeam->stables()->attach($this->stable->id, [
                'joined_at' => $secondTagTeamJoinDate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Verify all relationships exist
            expect($this->tagTeam->currentStable->id)->toBe($this->stable->id);
            expect($this->secondTagTeam->currentStable->id)->toBe($this->stable->id);

            // Verify stable has both tag teams
            expect($this->stable->currentTagTeams()->count())->toBe(2);

            // Verify total pivot record count
            $tagTeamCount = StableTagTeam::where('stable_id', $this->stable->id)
                ->whereNull('left_at')
                ->count();
            expect($tagTeamCount)->toBe(2);
        });

        test('tag team can be part of multiple stables across different time periods', function () {
            $firstPeriodStart = Carbon::now()->subYear();
            $firstPeriodEnd = Carbon::now()->subMonths(6);
            $secondPeriodStart = Carbon::now()->subMonths(3);

            // First stable membership (completed)
            $this->tagTeam->stables()->attach($this->stable->id, [
                'joined_at' => $firstPeriodStart,
                'left_at' => $firstPeriodEnd,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Second stable membership (current)
            $this->tagTeam->stables()->attach($this->secondStable->id, [
                'joined_at' => $secondPeriodStart,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Verify relationship counts
            expect($this->tagTeam->stables()->count())->toBe(2);
            expect($this->tagTeam->currentStable)->not()->toBeNull();
            expect($this->tagTeam->previousStables()->count())->toBe(1);

            // Verify current stable is correct
            $currentStable = $this->tagTeam->currentStable;
            expect($currentStable->id)->toBe($this->secondStable->id);
            expect(Carbon::parse($currentStable->pivot->joined_at)->format('Y-m-d H:i:s'))->toBe($secondPeriodStart->format('Y-m-d H:i:s'));
            expect($currentStable->pivot->left_at)->toBeNull();

            // Verify previous stable is correct
            $previousStable = $this->tagTeam->previousStables()->first();
            expect($previousStable->id)->toBe($this->stable->id);
            expect(Carbon::parse($previousStable->pivot->joined_at)->format('Y-m-d H:i:s'))->toBe($firstPeriodStart->format('Y-m-d H:i:s'));
            expect(Carbon::parse($previousStable->pivot->left_at)->format('Y-m-d H:i:s'))->toBe($firstPeriodEnd->format('Y-m-d H:i:s'));
        });
    });

    describe('TagTeam-Stable Membership Termination', function () {
        beforeEach(function () {
            // Set up active stable membership
            $this->tagTeam->stables()->attach($this->stable->id, [
                'joined_at' => Carbon::now()->subMonths(4),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        test('tag team leaving stable updates pivot correctly', function () {
            $leaveDate = Carbon::now();

            // End the relationship by updating the pivot
            $this->tagTeam->stables()->updateExistingPivot($this->stable->id, [
                'left_at' => $leaveDate,
                'updated_at' => now(),
            ]);

            // Verify relationship status changed
            expect($this->tagTeam->currentStable)->toBeNull();
            expect($this->tagTeam->previousStables()->count())->toBe(1);

            // Verify pivot data is updated
            $previousStable = $this->tagTeam->previousStables()->first();
            expect(Carbon::parse($previousStable->pivot->left_at)->format('Y-m-d H:i:s'))->toBe($leaveDate->format('Y-m-d H:i:s'));
        });

        test('detaching tag team completely removes relationship', function () {
            // Detach the tag team from stable
            $this->tagTeam->stables()->detach($this->stable->id);

            // Verify all relationships are gone
            expect($this->tagTeam->stables()->count())->toBe(0);
            expect($this->tagTeam->currentStable)->toBeNull();
            expect($this->tagTeam->previousStables()->count())->toBe(0);

            // Verify pivot record is deleted
            expect(StableTagTeam::where('tag_team_id', $this->tagTeam->id)
                ->where('stable_id', $this->stable->id)
                ->exists())->toBeFalse();
        });
    });

    describe('StableTagTeam Pivot Model Direct Queries', function () {
        test('StableTagTeam pivot model can be queried directly', function () {
            $this->tagTeam->stables()->attach($this->stable->id, [
                'joined_at' => Carbon::now()->subMonths(4),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $pivotRecord = StableTagTeam::where('tag_team_id', $this->tagTeam->id)
                ->where('stable_id', $this->stable->id)
                ->first();

            expect($pivotRecord)->not()->toBeNull();
            expect($pivotRecord->tag_team_id)->toBe($this->tagTeam->id);
            expect($pivotRecord->stable_id)->toBe($this->stable->id);
            expect($pivotRecord->joined_at)->toBeInstanceOf(Carbon::class);
            expect($pivotRecord->left_at)->toBeNull();

            // Test pivot relationships
            expect($pivotRecord->tagTeam->id)->toBe($this->tagTeam->id);
            expect($pivotRecord->stable->id)->toBe($this->stable->id);
        });

        test('pivot model handles date casting correctly', function () {
            $joinedDate = Carbon::now()->subMonths(4);
            $leftDate = Carbon::now()->subMonths(1);

            // Test tag team pivot
            $this->tagTeam->stables()->attach($this->stable->id, [
                'joined_at' => $joinedDate,
                'left_at' => $leftDate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $tagTeamPivot = StableTagTeam::where('tag_team_id', $this->tagTeam->id)
                ->where('stable_id', $this->stable->id)
                ->first();

            expect($tagTeamPivot->joined_at)->toBeInstanceOf(Carbon::class);
            expect($tagTeamPivot->left_at)->toBeInstanceOf(Carbon::class);
            expect($tagTeamPivot->joined_at->format('Y-m-d H:i:s'))->toBe($joinedDate->format('Y-m-d H:i:s'));
            expect($tagTeamPivot->left_at->format('Y-m-d H:i:s'))->toBe($leftDate->format('Y-m-d H:i:s'));
        });
    });

    describe('TagTeam Stable Queries', function () {
        beforeEach(function () {
            // Set up complex membership scenario
            $this->tagTeam->stables()->attach($this->stable->id, [
                'joined_at' => Carbon::now()->subYear(),
                'left_at' => Carbon::now()->subMonths(6),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->tagTeam->stables()->attach($this->secondStable->id, [
                'joined_at' => Carbon::now()->subMonths(3),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        test('current stable query returns only active relationship', function () {
            $currentStable = $this->tagTeam->currentStable;

            expect($currentStable)->not()->toBeNull();
            expect($currentStable->id)->toBe($this->secondStable->id);
            expect($currentStable->pivot->left_at)->toBeNull();
        });

        test('previous stables query returns only completed relationships', function () {
            $previousStables = $this->tagTeam->previousStables()->get();

            expect($previousStables)->toHaveCount(1);
            expect($previousStables->first()->id)->toBe($this->stable->id);
            expect($previousStables->first()->pivot->left_at)->not()->toBeNull();
        });

        test('all stables query returns complete membership history', function () {
            $allStables = $this->tagTeam->stables()->get();

            expect($allStables)->toHaveCount(2);

            $stableIds = $allStables->pluck('id')->toArray();
            expect($stableIds)->toContain($this->stable->id);
            expect($stableIds)->toContain($this->secondStable->id);
        });

        test('isNotCurrentlyInStable method works correctly', function () {
            // TagTeam is currently in secondStable, not in stable
            expect($this->tagTeam->isNotCurrentlyInStable($this->stable))->toBeTrue();
            expect($this->tagTeam->isNotCurrentlyInStable($this->secondStable))->toBeFalse();
        });
    });
});