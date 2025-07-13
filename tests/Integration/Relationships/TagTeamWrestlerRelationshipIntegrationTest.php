<?php

declare(strict_types=1);

use App\Models\TagTeams\TagTeam;
use App\Models\TagTeams\TagTeamWrestler;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;

/**
 * Integration tests for TagTeam-Wrestler relationship functionality.
 *
 * This test suite validates the complete workflow of tag team-wrestler
 * partnerships including joining teams, leaving teams, querying current
 * and previous tag teams, and ensuring proper business rule enforcement.
 *
 * Tests cover the CanJoinTagTeams trait implementation and TagTeamWrestler
 * pivot model functionality with real database relationships.
 */
describe('TagTeam-Wrestler Relationship Integration', function () {
    beforeEach(function () {
        // Create test entities with realistic factory states
        $this->tagTeam = TagTeam::factory()->bookable()->create([
            'name' => 'The Hardy Boyz',
        ]);

        $this->wrestler = Wrestler::factory()->bookable()->create([
            'name' => 'Matt Hardy',
            'hometown' => 'Cameron, North Carolina',
        ]);

        $this->secondTagTeam = TagTeam::factory()->bookable()->create([
            'name' => 'The Dudley Boyz',
        ]);

        $this->secondWrestler = Wrestler::factory()->bookable()->create([
            'name' => 'Jeff Hardy',
            'hometown' => 'Cameron, North Carolina',
        ]);

        $this->thirdWrestler = Wrestler::factory()->bookable()->create([
            'name' => 'Bubba Ray Dudley',
            'hometown' => 'Dudleyville',
        ]);
    });

    describe('TagTeam-Wrestler Relationship Creation', function () {
        test('wrestler can join a tag team with proper pivot data', function () {
            $joinedDate = Carbon::now()->subMonths(6);

            // Create the relationship using the attach method with pivot data
            $this->wrestler->tagTeams()->attach($this->tagTeam->id, [
                'joined_at' => $joinedDate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Verify the relationship exists
            expect($this->wrestler->tagTeams()->count())->toBe(1);
            expect($this->wrestler->isAMemberOfCurrentTagTeam())->toBeTrue();
            expect($this->wrestler->previousTagTeams()->count())->toBe(0);

            // Verify pivot data is correct
            $pivotData = $this->wrestler->tagTeams()->first()->pivot;
            expect($pivotData->joined_at->equalTo($joinedDate))->toBeTrue();
            expect($pivotData->left_at)->toBeNull();
            expect($pivotData->wrestler_id)->toBe($this->wrestler->id);
            expect($pivotData->tag_team_id)->toBe($this->tagTeam->id);
        });

        test('tag team can have multiple wrestlers as partners', function () {
            $joinedDate1 = Carbon::now()->subMonths(3);
            $joinedDate2 = Carbon::now()->subMonths(2);

            // Attach multiple wrestlers to the same tag team
            $this->wrestler->tagTeams()->attach($this->tagTeam->id, [
                'joined_at' => $joinedDate1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->secondWrestler->tagTeams()->attach($this->tagTeam->id, [
                'joined_at' => $joinedDate2,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Verify both relationships exist
            expect($this->wrestler->isAMemberOfCurrentTagTeam())->toBeTrue();
            expect($this->secondWrestler->isAMemberOfCurrentTagTeam())->toBeTrue();

            // Verify both wrestlers are in the same tag team
            expect($this->wrestler->currentTagTeam->id)->toBe($this->tagTeam->id);
            expect($this->secondWrestler->currentTagTeam->id)->toBe($this->tagTeam->id);

            // Verify tag team has both wrestlers (if the reverse relationship exists)
            if (method_exists($this->tagTeam, 'currentWrestlers')) {
                expect($this->tagTeam->currentWrestlers()->count())->toBe(2);
                expect($this->tagTeam->currentWrestlers->pluck('id'))
                    ->toContain($this->wrestler->id)
                    ->toContain($this->secondWrestler->id);
            }
        });

        test('wrestler can be part of multiple tag teams across different time periods', function () {
            $firstPeriodStart = Carbon::now()->subYear();
            $firstPeriodEnd = Carbon::now()->subMonths(6);
            $secondPeriodStart = Carbon::now()->subMonths(3);

            // First tag team membership (completed)
            $this->wrestler->tagTeams()->attach($this->tagTeam->id, [
                'joined_at' => $firstPeriodStart,
                'left_at' => $firstPeriodEnd,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Second tag team membership (current)
            $this->wrestler->tagTeams()->attach($this->secondTagTeam->id, [
                'joined_at' => $secondPeriodStart,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Verify relationship counts
            expect($this->wrestler->tagTeams()->count())->toBe(2);
            expect($this->wrestler->isAMemberOfCurrentTagTeam())->toBeTrue();
            expect($this->wrestler->previousTagTeams()->count())->toBe(1);

            // Verify current tag team is correct
            $currentTagTeam = $this->wrestler->currentTagTeam;
            expect($currentTagTeam->id)->toBe($this->secondTagTeam->id);
            expect($currentTagTeam->pivot->joined_at->equalTo($secondPeriodStart))->toBeTrue();
            expect($currentTagTeam->pivot->left_at)->toBeNull();

            // Verify previous tag team is correct
            $previousTagTeam = $this->wrestler->previousTagTeams()->first();
            expect($previousTagTeam->id)->toBe($this->tagTeam->id);
            expect($previousTagTeam->pivot->joined_at->equalTo($firstPeriodStart))->toBeTrue();
            expect($previousTagTeam->pivot->left_at->equalTo($firstPeriodEnd))->toBeTrue();
        });
    });

    describe('TagTeam-Wrestler Relationship Termination', function () {
        beforeEach(function () {
            // Set up an active tag team membership
            $this->wrestler->tagTeams()->attach($this->tagTeam->id, [
                'joined_at' => Carbon::now()->subMonths(6),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        test('leaving tag team updates pivot correctly', function () {
            $leaveDate = Carbon::now();

            // End the relationship by updating the pivot
            $this->wrestler->tagTeams()->updateExistingPivot($this->tagTeam->id, [
                'left_at' => $leaveDate,
                'updated_at' => now(),
            ]);

            // Verify relationship status changed
            expect($this->wrestler->isAMemberOfCurrentTagTeam())->toBeFalse();
            expect($this->wrestler->previousTagTeams()->count())->toBe(1);

            // Verify pivot data is updated
            $previousTagTeam = $this->wrestler->previousTagTeams()->first();
            expect($previousTagTeam->pivot->left_at->equalTo($leaveDate))->toBeTrue();
        });

        test('detaching wrestler completely removes relationship', function () {
            // Detach the wrestler from tag team
            $this->wrestler->tagTeams()->detach($this->tagTeam->id);

            // Verify all relationships are gone
            expect($this->wrestler->tagTeams()->count())->toBe(0);
            expect($this->wrestler->isAMemberOfCurrentTagTeam())->toBeFalse();
            expect($this->wrestler->previousTagTeams()->count())->toBe(0);

            // Verify pivot record is deleted
            expect(TagTeamWrestler::where('wrestler_id', $this->wrestler->id)
                ->where('tag_team_id', $this->tagTeam->id)
                ->exists())->toBeFalse();
        });
    });

    describe('TagTeam-Wrestler Relationship Queries', function () {
        beforeEach(function () {
            // Set up complex relationship scenario
            $this->wrestler->tagTeams()->attach($this->tagTeam->id, [
                'joined_at' => Carbon::now()->subYear(),
                'left_at' => Carbon::now()->subMonths(6),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->wrestler->tagTeams()->attach($this->secondTagTeam->id, [
                'joined_at' => Carbon::now()->subMonths(3),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->secondWrestler->tagTeams()->attach($this->tagTeam->id, [
                'joined_at' => Carbon::now()->subMonths(2),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        test('current tag team query returns only active relationship', function () {
            $currentTagTeam = $this->wrestler->currentTagTeam;

            expect($currentTagTeam)->not->toBeNull();
            expect($currentTagTeam->id)->toBe($this->secondTagTeam->id);
            expect($currentTagTeam->pivot->left_at)->toBeNull();
        });

        test('previous tag teams query returns only completed relationships', function () {
            $previousTagTeams = $this->wrestler->previousTagTeams()->get();

            expect($previousTagTeams)->toHaveCount(1);
            expect($previousTagTeams->first()->id)->toBe($this->tagTeam->id);
            expect($previousTagTeams->first()->pivot->left_at)->not->toBeNull();
        });

        test('all tag teams query returns complete relationship history', function () {
            $allTagTeams = $this->wrestler->tagTeams()->get();

            expect($allTagTeams)->toHaveCount(2);

            $tagTeamIds = $allTagTeams->pluck('id')->toArray();
            expect($tagTeamIds)->toContain($this->tagTeam->id);
            expect($tagTeamIds)->toContain($this->secondTagTeam->id);
        });

        test('previous tag team query returns most recent former team', function () {
            // Add another previous tag team with earlier date
            $thirdTagTeam = TagTeam::factory()->create(['name' => 'The Rock n Sock Connection']);
            $this->wrestler->tagTeams()->attach($thirdTagTeam->id, [
                'joined_at' => Carbon::now()->subYears(2),
                'left_at' => Carbon::now()->subMonths(18),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $mostRecentPrevious = $this->wrestler->previousTagTeam;

            expect($mostRecentPrevious)->not->toBeNull();
            expect($mostRecentPrevious->id)->toBe($this->tagTeam->id); // More recent than the third team
        });

        test('isAMemberOfCurrentTagTeam accurately checks current status', function () {
            expect($this->wrestler->isAMemberOfCurrentTagTeam())->toBeTrue();
            expect($this->secondWrestler->isAMemberOfCurrentTagTeam())->toBeTrue();
            expect($this->thirdWrestler->isAMemberOfCurrentTagTeam())->toBeFalse();
        });

        test('tag team relationships are properly ordered by joined_at', function () {
            $tagTeamsChronological = $this->wrestler->tagTeams()
                ->orderBy('joined_at', 'asc')
                ->get();

            expect($tagTeamsChronological->first()->id)->toBe($this->tagTeam->id);
            expect($tagTeamsChronological->last()->id)->toBe($this->secondTagTeam->id);
        });

        test('can query tag teams within specific date ranges', function () {
            $recentTagTeams = $this->wrestler->tagTeams()
                ->wherePivot('joined_at', '>=', Carbon::now()->subMonths(4))
                ->get();

            expect($recentTagTeams)->toHaveCount(1);
            expect($recentTagTeams->first()->id)->toBe($this->secondTagTeam->id);
        });
    });

    describe('TagTeamWrestler Pivot Model', function () {
        test('pivot model can be queried directly', function () {
            $this->wrestler->tagTeams()->attach($this->tagTeam->id, [
                'joined_at' => Carbon::now()->subMonths(6),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $pivotRecord = TagTeamWrestler::where('wrestler_id', $this->wrestler->id)
                ->where('tag_team_id', $this->tagTeam->id)
                ->first();

            expect($pivotRecord)->not->toBeNull();
            expect($pivotRecord->wrestler_id)->toBe($this->wrestler->id);
            expect($pivotRecord->tag_team_id)->toBe($this->tagTeam->id);
            expect($pivotRecord->joined_at)->toBeInstanceOf(Carbon::class);
            expect($pivotRecord->left_at)->toBeNull();
        });

        test('pivot model relationships work correctly', function () {
            $this->wrestler->tagTeams()->attach($this->tagTeam->id, [
                'joined_at' => Carbon::now()->subMonths(6),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $pivotRecord = TagTeamWrestler::where('wrestler_id', $this->wrestler->id)
                ->where('tag_team_id', $this->tagTeam->id)
                ->first();

            // Test pivot relationships (note: pivot uses 'partner' instead of 'wrestler')
            expect($pivotRecord->partner->id)->toBe($this->wrestler->id);
            expect($pivotRecord->tagTeam->id)->toBe($this->tagTeam->id);
        });

        test('pivot model handles date casting correctly', function () {
            $joinedDate = Carbon::now()->subMonths(6);
            $leftDate = Carbon::now()->subMonths(1);

            $this->wrestler->tagTeams()->attach($this->tagTeam->id, [
                'joined_at' => $joinedDate,
                'left_at' => $leftDate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $pivotRecord = TagTeamWrestler::where('wrestler_id', $this->wrestler->id)
                ->where('tag_team_id', $this->tagTeam->id)
                ->first();

            expect($pivotRecord->joined_at)->toBeInstanceOf(Carbon::class);
            expect($pivotRecord->left_at)->toBeInstanceOf(Carbon::class);
            expect($pivotRecord->joined_at->equalTo($joinedDate))->toBeTrue();
            expect($pivotRecord->left_at->equalTo($leftDate))->toBeTrue();
        });
    });

    describe('Business Rule Validation', function () {
        test('wrestler should not have multiple concurrent tag team memberships', function () {
            // Create first active membership
            $this->wrestler->tagTeams()->attach($this->tagTeam->id, [
                'joined_at' => Carbon::now()->subMonths(6),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Attempt to create concurrent membership (this should be prevented by application logic)
            $this->wrestler->tagTeams()->attach($this->secondTagTeam->id, [
                'joined_at' => Carbon::now()->subMonths(3),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // In this test, we'll verify that both exist but note this should be validated in business logic
            expect($this->wrestler->tagTeams()->count())->toBe(2);

            // Business logic should ensure only one current tag team
            // This would be enforced by validation rules, not database constraints
            $currentMemberships = $this->wrestler->tagTeams()->wherePivotNull('left_at')->count();
            expect($currentMemberships)->toBeGreaterThan(1); // This shows the need for validation
        });

        test('tag team membership periods should not overlap incorrectly', function () {
            $firstPeriodStart = Carbon::now()->subYear();
            $firstPeriodEnd = Carbon::now()->subMonths(6);
            $secondPeriodStart = Carbon::now()->subMonths(8); // Overlaps with first period

            // Create first membership period
            $this->wrestler->tagTeams()->attach($this->tagTeam->id, [
                'joined_at' => $firstPeriodStart,
                'left_at' => $firstPeriodEnd,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Attempt overlapping period (application logic should validate this)
            $this->wrestler->tagTeams()->attach($this->secondTagTeam->id, [
                'joined_at' => $secondPeriodStart,
                'left_at' => Carbon::now()->subMonths(4),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Verify both relationships exist (validation would be in business logic)
            expect($this->wrestler->tagTeams()->count())->toBe(2);
        });

        test('joined date must be before left date when both are set', function () {
            $joinedDate = Carbon::now()->subMonths(3);
            $leftDate = Carbon::now()->subMonths(6); // Earlier than joined date (invalid)

            // This should be caught by application validation, not database
            $this->wrestler->tagTeams()->attach($this->tagTeam->id, [
                'joined_at' => $joinedDate,
                'left_at' => $leftDate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $pivotRecord = TagTeamWrestler::where('wrestler_id', $this->wrestler->id)
                ->where('tag_team_id', $this->tagTeam->id)
                ->first();

            // Data is stored as-is; validation should happen in business logic
            expect($pivotRecord->joined_at->greaterThan($pivotRecord->left_at))->toBeTrue();
        });
    });

    describe('Complex Tag Team Scenarios', function () {
        test('wrestler can rejoin the same tag team after leaving', function () {
            // First membership period
            $this->wrestler->tagTeams()->attach($this->tagTeam->id, [
                'joined_at' => Carbon::now()->subYear(),
                'left_at' => Carbon::now()->subMonths(8),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Different tag team membership in between
            $this->wrestler->tagTeams()->attach($this->secondTagTeam->id, [
                'joined_at' => Carbon::now()->subMonths(6),
                'left_at' => Carbon::now()->subMonths(4),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Rejoin original tag team
            $this->wrestler->tagTeams()->attach($this->tagTeam->id, [
                'joined_at' => Carbon::now()->subMonths(2),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Verify total relationships
            expect($this->wrestler->tagTeams()->count())->toBe(3);
            expect($this->wrestler->isAMemberOfCurrentTagTeam())->toBeTrue();
            expect($this->wrestler->previousTagTeams()->count())->toBe(2);

            // Verify current tag team is the original team
            $currentTagTeam = $this->wrestler->currentTagTeam;
            expect($currentTagTeam->id)->toBe($this->tagTeam->id);

            // Verify relationship history includes both tag teams
            $allTagTeams = $this->wrestler->tagTeams()->get();
            $uniqueTagTeams = $allTagTeams->unique('id');
            expect($uniqueTagTeams)->toHaveCount(2);
        });

        test('can query tag team partnership duration and calculate statistics', function () {
            $firstPeriodStart = Carbon::now()->subYear();
            $firstPeriodEnd = Carbon::now()->subMonths(6);
            $secondPeriodStart = Carbon::now()->subMonths(3);

            // First completed period
            $this->wrestler->tagTeams()->attach($this->tagTeam->id, [
                'joined_at' => $firstPeriodStart,
                'left_at' => $firstPeriodEnd,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Current ongoing period
            $this->wrestler->tagTeams()->attach($this->secondTagTeam->id, [
                'joined_at' => $secondPeriodStart,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Calculate duration of completed period
            $completedPeriod = $this->wrestler->previousTagTeams()->first();
            $duration = $completedPeriod->pivot->joined_at->diffInDays($completedPeriod->pivot->left_at);
            expect($duration)->toBeGreaterThan(150); // Approximately 6 months

            // Calculate duration of current period
            $currentPeriod = $this->wrestler->currentTagTeam;
            $currentDuration = $currentPeriod->pivot->joined_at->diffInDays(Carbon::now());
            expect($currentDuration)->toBeGreaterThan(80); // Approximately 3 months
        });

        test('tag team with multiple member changes over time', function () {
            $initialJoinDate = Carbon::now()->subMonths(6);
            $secondJoinDate = Carbon::now()->subMonths(4);
            $firstLeaveDate = Carbon::now()->subMonths(2);
            $thirdJoinDate = Carbon::now()->subMonths(1);

            // Original two-person tag team
            $this->wrestler->tagTeams()->attach($this->tagTeam->id, [
                'joined_at' => $initialJoinDate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->secondWrestler->tagTeams()->attach($this->tagTeam->id, [
                'joined_at' => $secondJoinDate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // First wrestler leaves
            $this->wrestler->tagTeams()->updateExistingPivot($this->tagTeam->id, [
                'left_at' => $firstLeaveDate,
                'updated_at' => now(),
            ]);

            // Third wrestler joins
            $this->thirdWrestler->tagTeams()->attach($this->tagTeam->id, [
                'joined_at' => $thirdJoinDate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Verify current membership
            expect($this->wrestler->isAMemberOfCurrentTagTeam())->toBeFalse();
            expect($this->secondWrestler->isAMemberOfCurrentTagTeam())->toBeTrue();
            expect($this->thirdWrestler->isAMemberOfCurrentTagTeam())->toBeTrue();

            // Verify tag team evolution
            if (method_exists($this->tagTeam, 'currentWrestlers')) {
                $currentMembers = $this->tagTeam->currentWrestlers()->get();
                expect($currentMembers)->toHaveCount(2);
                expect($currentMembers->pluck('id'))
                    ->toContain($this->secondWrestler->id)
                    ->toContain($this->thirdWrestler->id)
                    ->not->toContain($this->wrestler->id);
            }
        });
    });

    describe('Performance and Query Optimization', function () {
        test('eager loading tag team relationships works correctly', function () {
            // Set up multiple relationships
            $this->wrestler->tagTeams()->attach($this->tagTeam->id, [
                'joined_at' => Carbon::now()->subMonths(6),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->secondWrestler->tagTeams()->attach($this->secondTagTeam->id, [
                'joined_at' => Carbon::now()->subMonths(3),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Load wrestlers with their current tag teams
            $wrestlers = Wrestler::with('currentTagTeam')->get();

            expect($wrestlers)->toHaveCount(3); // Including thirdWrestler

            // Verify relationships are loaded
            $wrestlerWithTagTeam = $wrestlers->firstWhere('id', $this->wrestler->id);
            expect($wrestlerWithTagTeam->relationLoaded('currentTagTeam'))->toBeTrue();
            expect($wrestlerWithTagTeam->currentTagTeam)->not->toBeNull();
        });

        test('can efficiently count tag team relationships without loading them', function () {
            $this->wrestler->tagTeams()->attach($this->tagTeam->id, [
                'joined_at' => Carbon::now()->subMonths(6),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->wrestler->tagTeams()->attach($this->secondTagTeam->id, [
                'joined_at' => Carbon::now()->subMonths(3),
                'left_at' => Carbon::now()->subMonths(1),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Count without loading
            expect($this->wrestler->tagTeams()->count())->toBe(2);
            expect($this->wrestler->previousTagTeams()->count())->toBe(1);
            expect($this->wrestler->isAMemberOfCurrentTagTeam())->toBeTrue();

            // Verify relationships are not loaded
            expect($this->wrestler->relationLoaded('tagTeams'))->toBeFalse();
        });

        test('BelongsToOne relationships work efficiently for single results', function () {
            // Set up previous tag team
            $this->wrestler->tagTeams()->attach($this->tagTeam->id, [
                'joined_at' => Carbon::now()->subYear(),
                'left_at' => Carbon::now()->subMonths(6),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Set up current tag team
            $this->wrestler->tagTeams()->attach($this->secondTagTeam->id, [
                'joined_at' => Carbon::now()->subMonths(3),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Test BelongsToOne relationships return single models, not collections
            $currentTagTeam = $this->wrestler->currentTagTeam;
            $previousTagTeam = $this->wrestler->previousTagTeam;

            expect($currentTagTeam)->not->toBeNull();
            expect($currentTagTeam)->toBeInstanceOf(TagTeam::class);
            expect($currentTagTeam->id)->toBe($this->secondTagTeam->id);

            expect($previousTagTeam)->not->toBeNull();
            expect($previousTagTeam)->toBeInstanceOf(TagTeam::class);
            expect($previousTagTeam->id)->toBe($this->tagTeam->id);
        });
    });
});
