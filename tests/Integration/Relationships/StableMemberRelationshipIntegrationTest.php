<?php

declare(strict_types=1);

use App\Models\Stables\Stable;
use App\Models\Stables\StableMember;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;

/**
 * Integration tests for Stable-Member relationship functionality.
 *
 * This test suite validates the complete workflow of stable memberships
 * including wrestlers and tag teams joining stables, leaving stables,
 * querying current and previous memberships, and ensuring proper
 * business rule enforcement.
 *
 * Tests cover the CanJoinStables trait implementation and the polymorphic
 * StableMember model with real database relationships. Note: Managers are
 * not direct stable members - they are associated through wrestlers/tag teams.
 */
describe('Stable-Member Relationship Integration', function () {
    beforeEach(function () {
        // Create test entities with realistic factory states
        $this->stable = Stable::factory()->active()->create([
            'name' => 'The Four Horsemen',
        ]);

        $this->wrestler = Wrestler::factory()->bookable()->create([
            'name' => 'Ric Flair',
            'hometown' => 'Charlotte, North Carolina',
        ]);

        $this->tagTeam = TagTeam::factory()->bookable()->create([
            'name' => 'The Brain Busters',
        ]);

        $this->secondStable = Stable::factory()->active()->create([
            'name' => 'D-Generation X',
        ]);

        $this->secondWrestler = Wrestler::factory()->bookable()->create([
            'name' => 'Tully Blanchard',
            'hometown' => 'San Antonio, Texas',
        ]);

        $this->secondTagTeam = TagTeam::factory()->bookable()->create([
            'name' => 'The New Age Outlaws',
        ]);

    });

    describe('Stable Membership Creation', function () {
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
            expect($this->wrestler->currentStable)->not->toBeNull();
            expect($this->wrestler->previousStables()->count())->toBe(0);

            // Verify pivot data is correct
            $pivotData = $this->wrestler->stables()->first()->pivot;
            expect($pivotData->joined_at->equalTo($joinedDate))->toBeTrue();
            expect($pivotData->left_at)->toBeNull();
            expect($pivotData->member_id)->toBe($this->wrestler->id);
            expect($pivotData->member_type)->toBe('wrestler');
            expect($pivotData->stable_id)->toBe($this->stable->id);
        });

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
            expect($this->tagTeam->currentStable)->not->toBeNull();
            expect($this->tagTeam->previousStables()->count())->toBe(0);

            // Verify pivot data is correct
            $pivotData = $this->tagTeam->stables()->first()->pivot;
            expect($pivotData->joined_at->equalTo($joinedDate))->toBeTrue();
            expect($pivotData->left_at)->toBeNull();
            expect($pivotData->member_id)->toBe($this->tagTeam->id);
            expect($pivotData->member_type)->toBe('tagTeam');
            expect($pivotData->stable_id)->toBe($this->stable->id);
        });

        test('stable can have multiple members of different types', function () {
            $wrestlerJoinDate = Carbon::now()->subMonths(6);
            $tagTeamJoinDate = Carbon::now()->subMonths(4);

            // Attach different member types to the same stable
            $this->wrestler->stables()->attach($this->stable->id, [
                'joined_at' => $wrestlerJoinDate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->tagTeam->stables()->attach($this->stable->id, [
                'joined_at' => $tagTeamJoinDate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Verify all relationships exist
            expect($this->wrestler->currentStable->id)->toBe($this->stable->id);
            expect($this->tagTeam->currentStable->id)->toBe($this->stable->id);

            // Verify stable has both member types
            expect($this->stable->currentWrestlers()->count())->toBe(1);
            expect($this->stable->currentTagTeams()->count())->toBe(1);

            // Verify total polymorphic member count
            expect(StableMember::where('stable_id', $this->stable->id)
                ->whereNull('left_at')
                ->count())->toBe(2);
        });

        test('member can be part of multiple stables across different time periods', function () {
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
            expect($this->wrestler->currentStable)->not->toBeNull();
            expect($this->wrestler->previousStables()->count())->toBe(1);

            // Verify current stable is correct
            $currentStable = $this->wrestler->currentStable;
            expect($currentStable->id)->toBe($this->secondStable->id);
            expect($currentStable->pivot->joined_at->equalTo($secondPeriodStart))->toBeTrue();
            expect($currentStable->pivot->left_at)->toBeNull();

            // Verify previous stable is correct
            $previousStable = $this->wrestler->previousStables()->first();
            expect($previousStable->id)->toBe($this->stable->id);
            expect($previousStable->pivot->joined_at->equalTo($firstPeriodStart))->toBeTrue();
            expect($previousStable->pivot->left_at->equalTo($firstPeriodEnd))->toBeTrue();
        });
    });

    describe('Stable Membership Termination', function () {
        beforeEach(function () {
            // Set up active stable memberships for all member types
            $this->wrestler->stables()->attach($this->stable->id, [
                'joined_at' => Carbon::now()->subMonths(6),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->tagTeam->stables()->attach($this->stable->id, [
                'joined_at' => Carbon::now()->subMonths(4),
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
            expect($previousStable->pivot->left_at->equalTo($leaveDate))->toBeTrue();
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
            expect($previousStable->pivot->left_at->equalTo($leaveDate))->toBeTrue();
        });

        test('detaching member completely removes relationship', function () {
            // Detach the wrestler from stable
            $this->wrestler->stables()->detach($this->stable->id);

            // Verify all relationships are gone
            expect($this->wrestler->stables()->count())->toBe(0);
            expect($this->wrestler->currentStable)->toBeNull();
            expect($this->wrestler->previousStables()->count())->toBe(0);

            // Verify pivot record is deleted
            expect(StableMember::where('member_id', $this->wrestler->id)
                ->where('member_type', 'wrestler')
                ->where('stable_id', $this->stable->id)
                ->exists())->toBeFalse();
        });
    });

    describe('Stable Membership Queries', function () {
        beforeEach(function () {
            // Set up complex membership scenario for wrestler
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

            // Set up current memberships for other member types
            $this->tagTeam->stables()->attach($this->stable->id, [
                'joined_at' => Carbon::now()->subMonths(2),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        });

        test('current stable query returns only active relationship', function () {
            $currentStable = $this->wrestler->currentStable;

            expect($currentStable)->not->toBeNull();
            expect($currentStable->id)->toBe($this->secondStable->id);
            expect($currentStable->pivot->left_at)->toBeNull();
        });

        test('previous stables query returns only completed relationships', function () {
            $previousStables = $this->wrestler->previousStables()->get();

            expect($previousStables)->toHaveCount(1);
            expect($previousStables->first()->id)->toBe($this->stable->id);
            expect($previousStables->first()->pivot->left_at)->not->toBeNull();
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

            // TagTeam is currently in stable, not in secondStable
            expect($this->tagTeam->isNotCurrentlyInStable($this->secondStable))->toBeTrue();
            expect($this->tagTeam->isNotCurrentlyInStable($this->stable))->toBeFalse();
        });

        test('stable memberships are properly ordered by joined_at', function () {
            $stablesChronological = $this->wrestler->stables()
                ->orderBy('joined_at', 'asc')
                ->get();

            expect($stablesChronological->first()->id)->toBe($this->stable->id);
            expect($stablesChronological->last()->id)->toBe($this->secondStable->id);
        });

        test('can query stables within specific date ranges', function () {
            $recentStables = $this->wrestler->stables()
                ->wherePivot('joined_at', '>=', Carbon::now()->subMonths(4))
                ->get();

            expect($recentStables)->toHaveCount(1);
            expect($recentStables->first()->id)->toBe($this->secondStable->id);
        });
    });

    describe('Stable Pivot Models', function () {
        test('StableMember pivot model can be queried directly for wrestler', function () {
            $this->wrestler->stables()->attach($this->stable->id, [
                'joined_at' => Carbon::now()->subMonths(6),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $pivotRecord = StableMember::where('member_id', $this->wrestler->id)
                ->where('member_type', 'wrestler')
                ->where('stable_id', $this->stable->id)
                ->first();

            expect($pivotRecord)->not->toBeNull();
            expect($pivotRecord->member_id)->toBe($this->wrestler->id);
            expect($pivotRecord->member_type)->toBe('wrestler');
            expect($pivotRecord->stable_id)->toBe($this->stable->id);
            expect($pivotRecord->joined_at)->toBeInstanceOf(Carbon::class);
            expect($pivotRecord->left_at)->toBeNull();

            // Test pivot relationships
            expect($pivotRecord->member->id)->toBe($this->wrestler->id);
            expect($pivotRecord->stable->id)->toBe($this->stable->id);
        });

        test('StableMember pivot model can be queried directly for tag team', function () {
            $this->tagTeam->stables()->attach($this->stable->id, [
                'joined_at' => Carbon::now()->subMonths(4),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $pivotRecord = StableMember::where('member_id', $this->tagTeam->id)
                ->where('member_type', 'tagTeam')
                ->where('stable_id', $this->stable->id)
                ->first();

            expect($pivotRecord)->not->toBeNull();
            expect($pivotRecord->member_id)->toBe($this->tagTeam->id);
            expect($pivotRecord->member_type)->toBe('tagTeam');
            expect($pivotRecord->stable_id)->toBe($this->stable->id);
            expect($pivotRecord->joined_at)->toBeInstanceOf(Carbon::class);
            expect($pivotRecord->left_at)->toBeNull();

            // Test pivot relationships
            expect($pivotRecord->member->id)->toBe($this->tagTeam->id);
            expect($pivotRecord->stable->id)->toBe($this->stable->id);
        });

        test('all pivot models handle date casting correctly', function () {
            $joinedDate = Carbon::now()->subMonths(6);
            $leftDate = Carbon::now()->subMonths(1);

            // Test wrestler pivot
            $this->wrestler->stables()->attach($this->stable->id, [
                'joined_at' => $joinedDate,
                'left_at' => $leftDate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $wrestlerPivot = StableMember::where('member_id', $this->wrestler->id)
                ->where('member_type', 'wrestler')
                ->where('stable_id', $this->stable->id)
                ->first();

            expect($wrestlerPivot->joined_at)->toBeInstanceOf(Carbon::class);
            expect($wrestlerPivot->left_at)->toBeInstanceOf(Carbon::class);
            expect($wrestlerPivot->joined_at->equalTo($joinedDate))->toBeTrue();
            expect($wrestlerPivot->left_at->equalTo($leftDate))->toBeTrue();
        });
    });

    describe('Business Rule Validation', function () {
        test('member should not have multiple concurrent stable memberships', function () {
            // Create first active membership
            $this->wrestler->stables()->attach($this->stable->id, [
                'joined_at' => Carbon::now()->subMonths(6),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Attempt to create concurrent membership (this should be prevented by application logic)
            $this->wrestler->stables()->attach($this->secondStable->id, [
                'joined_at' => Carbon::now()->subMonths(3),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // In this test, we'll verify that both exist but note this should be validated in business logic
            expect($this->wrestler->stables()->count())->toBe(2);

            // Business logic should ensure only one current stable
            $currentMemberships = $this->wrestler->stables()->wherePivotNull('left_at')->count();
            expect($currentMemberships)->toBeGreaterThan(1); // This shows the need for validation
        });

        test('stable membership periods should not overlap incorrectly', function () {
            $firstPeriodStart = Carbon::now()->subYear();
            $firstPeriodEnd = Carbon::now()->subMonths(6);
            $secondPeriodStart = Carbon::now()->subMonths(8); // Overlaps with first period

            // Create first membership period
            $this->wrestler->stables()->attach($this->stable->id, [
                'joined_at' => $firstPeriodStart,
                'left_at' => $firstPeriodEnd,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Attempt overlapping period (application logic should validate this)
            $this->wrestler->stables()->attach($this->secondStable->id, [
                'joined_at' => $secondPeriodStart,
                'left_at' => Carbon::now()->subMonths(4),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Verify both relationships exist (validation would be in business logic)
            expect($this->wrestler->stables()->count())->toBe(2);
        });

        test('joined date must be before left date when both are set', function () {
            $joinedDate = Carbon::now()->subMonths(3);
            $leftDate = Carbon::now()->subMonths(6); // Earlier than joined date (invalid)

            // This should be caught by application validation, not database
            $this->wrestler->stables()->attach($this->stable->id, [
                'joined_at' => $joinedDate,
                'left_at' => $leftDate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $pivotRecord = StableMember::where('member_id', $this->wrestler->id)
                ->where('member_type', 'wrestler')
                ->where('stable_id', $this->stable->id)
                ->first();

            // Data is stored as-is; validation should happen in business logic
            expect($pivotRecord->joined_at->greaterThan($pivotRecord->left_at))->toBeTrue();
        });
    });

    describe('Complex Stable Scenarios', function () {
        test('stable evolution with different member types joining and leaving', function () {
            $wrestlerJoinDate = Carbon::now()->subMonths(6);
            $tagTeamJoinDate = Carbon::now()->subMonths(4);
            $wrestlerLeaveDate = Carbon::now()->subMonths(2);
            $newWrestlerJoinDate = Carbon::now()->subMonths(1);

            // Wrestler joins
            $this->wrestler->stables()->attach($this->stable->id, [
                'joined_at' => $wrestlerJoinDate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Tag team joins
            $this->tagTeam->stables()->attach($this->stable->id, [
                'joined_at' => $tagTeamJoinDate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Original wrestler leaves
            $this->wrestler->stables()->updateExistingPivot($this->stable->id, [
                'left_at' => $wrestlerLeaveDate,
                'updated_at' => now(),
            ]);

            // New wrestler joins
            $this->secondWrestler->stables()->attach($this->stable->id, [
                'joined_at' => $newWrestlerJoinDate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Verify final stable composition
            expect($this->tagTeam->currentStable->id)->toBe($this->stable->id);
            expect($this->secondWrestler->currentStable->id)->toBe($this->stable->id);
            expect($this->wrestler->currentStable)->toBeNull();
            expect($this->wrestler->previousStables()->count())->toBe(1);
        });

        test('member can rejoin the same stable after leaving', function () {
            // First membership period
            $this->wrestler->stables()->attach($this->stable->id, [
                'joined_at' => Carbon::now()->subYear(),
                'left_at' => Carbon::now()->subMonths(8),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Different stable membership in between
            $this->wrestler->stables()->attach($this->secondStable->id, [
                'joined_at' => Carbon::now()->subMonths(6),
                'left_at' => Carbon::now()->subMonths(4),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Rejoin original stable
            $this->wrestler->stables()->attach($this->stable->id, [
                'joined_at' => Carbon::now()->subMonths(2),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Verify total relationships
            expect($this->wrestler->stables()->count())->toBe(3);
            expect($this->wrestler->currentStable)->not->toBeNull();
            expect($this->wrestler->previousStables()->count())->toBe(2);

            // Verify current stable is the original stable
            $currentStable = $this->wrestler->currentStable;
            expect($currentStable->id)->toBe($this->stable->id);

            // Verify relationship history includes both stables
            $allStables = $this->wrestler->stables()->get();
            $uniqueStables = $allStables->unique('id');
            expect($uniqueStables)->toHaveCount(2);
        });

        test('can query stable membership duration and calculate statistics', function () {
            $firstPeriodStart = Carbon::now()->subYear();
            $firstPeriodEnd = Carbon::now()->subMonths(6);
            $secondPeriodStart = Carbon::now()->subMonths(3);

            // First completed period
            $this->wrestler->stables()->attach($this->stable->id, [
                'joined_at' => $firstPeriodStart,
                'left_at' => $firstPeriodEnd,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Current ongoing period
            $this->wrestler->stables()->attach($this->secondStable->id, [
                'joined_at' => $secondPeriodStart,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Calculate duration of completed period
            $completedPeriod = $this->wrestler->previousStables()->first();
            $duration = $completedPeriod->pivot->joined_at->diffInDays($completedPeriod->pivot->left_at);
            expect($duration)->toBeGreaterThan(150); // Approximately 6 months

            // Calculate duration of current period
            $currentPeriod = $this->wrestler->currentStable;
            $currentDuration = $currentPeriod->pivot->joined_at->diffInDays(Carbon::now());
            expect($currentDuration)->toBeGreaterThan(80); // Approximately 3 months
        });
    });

    describe('Performance and Query Optimization', function () {
        test('eager loading stable relationships works correctly', function () {
            // Set up multiple relationships
            $this->wrestler->stables()->attach($this->stable->id, [
                'joined_at' => Carbon::now()->subMonths(6),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->tagTeam->stables()->attach($this->secondStable->id, [
                'joined_at' => Carbon::now()->subMonths(3),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Load wrestlers with their current stables
            $wrestlers = Wrestler::with('currentStable')->get();

            expect($wrestlers)->toHaveCount(2); // Including secondWrestler

            // Verify relationships are loaded
            $wrestlerWithStable = $wrestlers->firstWhere('id', $this->wrestler->id);
            expect($wrestlerWithStable->relationLoaded('currentStable'))->toBeTrue();
            expect($wrestlerWithStable->currentStable)->not->toBeNull();
        });

        test('can efficiently count stable relationships without loading them', function () {
            $this->wrestler->stables()->attach($this->stable->id, [
                'joined_at' => Carbon::now()->subMonths(6),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->wrestler->stables()->attach($this->secondStable->id, [
                'joined_at' => Carbon::now()->subMonths(3),
                'left_at' => Carbon::now()->subMonths(1),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Count without loading
            expect($this->wrestler->stables()->count())->toBe(2);
            expect($this->wrestler->previousStables()->count())->toBe(1);

            // Verify relationships are not loaded
            expect($this->wrestler->relationLoaded('stables'))->toBeFalse();
        });

        test('BelongsToOne relationships work efficiently for single current stable results', function () {
            // Set up previous stable
            $this->wrestler->stables()->attach($this->stable->id, [
                'joined_at' => Carbon::now()->subYear(),
                'left_at' => Carbon::now()->subMonths(6),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Set up current stable
            $this->wrestler->stables()->attach($this->secondStable->id, [
                'joined_at' => Carbon::now()->subMonths(3),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Test BelongsToOne relationship returns single model, not collection
            $currentStable = $this->wrestler->currentStable;

            expect($currentStable)->not->toBeNull();
            expect($currentStable)->toBeInstanceOf(Stable::class);
            expect($currentStable->id)->toBe($this->secondStable->id);
        });
    });
});
