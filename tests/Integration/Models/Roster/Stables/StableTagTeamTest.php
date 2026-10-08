<?php

declare(strict_types=1);

use App\Models\Roster\Stables\Stable;
use App\Models\Roster\Stables\StableTagTeam;
use App\Models\Roster\TagTeams\TagTeam;
use Illuminate\Support\Carbon;

/**
 * @return array{
 *     stable: Stable,
 *     tagTeam: TagTeam,
 *     secondStable: Stable,
 *     secondTagTeam: TagTeam,
 * }
 */
function stablesStableTagTeamStableFixtures(): array
{
    // Create test entities with realistic factory states
    $stable = Stable::factory()->unactivated()->create([
        'name' => 'The Four Horsemen',
    ]);

    $tagTeam = TagTeam::factory()->employed()->create([
        'name' => 'The Brain Busters',
    ]);

    $secondStable = Stable::factory()->unactivated()->create([
        'name' => 'D-Generation X',
    ]);

    $secondTagTeam = TagTeam::factory()->employed()->create([
        'name' => 'The New Age Outlaws',
    ]);

    return [
        'stable' => $stable,
        'tagTeam' => $tagTeam,
        'secondStable' => $secondStable,
        'secondTagTeam' => $secondTagTeam,
    ];
}

function stablesStableTagTeamTagTeamSetupFixtures(TagTeam $tagTeam, Stable $stable): void
{
    // Set up active stable membership
    $tagTeam->stables()->attach($stable->id, [
        'joined_at' => Carbon::now()->subMonths(4),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

}

function stablesStableTagTeamTagTeamSetupFixtures2(TagTeam $tagTeam, Stable $stable, Stable $secondStable): void
{
    // Set up complex membership scenario
    $tagTeam->stables()->attach($stable->id, [
        'joined_at' => Carbon::now()->subYear(),
        'left_at' => Carbon::now()->subMonths(6),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tagTeam->stables()->attach($secondStable->id, [
        'joined_at' => Carbon::now()->subMonths(3),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

}

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
 * @see StableTagTeam
 */
describe('StableTagTeam Pivot Model', function () {
    describe('TagTeam-Stable Membership Creation', function () {
        test('tag team can join a stable with proper pivot data', function () {
            ['stable' => $stable, 'tagTeam' => $tagTeam] = stablesStableTagTeamStableFixtures();

            $joinedDate = Carbon::now()->subMonths(4);

            // Create the relationship using the attach method with pivot data
            $tagTeam->stables()->attach($stable->id, [
                'joined_at' => $joinedDate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Verify the relationship exists
            expect($tagTeam->stables()->count())->toBe(1);
            expect($tagTeam->currentStable)->not()->toBeNull()
                ->and($tagTeam->previousStables()->count())->toBe(0);

            // Verify pivot data is correct
            $pivotData = $tagTeam->stables()->firstOrFail()->pivot;
            expect(Carbon::parse($pivotData->joined_at)->format('Y-m-d H:i:s'))->toBe($joinedDate->format('Y-m-d H:i:s'))
                ->and($pivotData->left_at)->toBeNull()
                ->and($pivotData->tag_team_id)->toBe($tagTeam->id)
                ->and($pivotData->stable_id)->toBe($stable->id);
        });

        test('stable can have multiple tag teams', function () {
            ['stable' => $stable, 'tagTeam' => $tagTeam, 'secondTagTeam' => $secondTagTeam] = stablesStableTagTeamStableFixtures();

            $tagTeamJoinDate = Carbon::now()->subMonths(6);
            $secondTagTeamJoinDate = Carbon::now()->subMonths(4);

            // Attach different tag teams to the same stable
            $tagTeam->stables()->attach($stable->id, [
                'joined_at' => $tagTeamJoinDate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $secondTagTeam->stables()->attach($stable->id, [
                'joined_at' => $secondTagTeamJoinDate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Verify all relationships exist
            expect($tagTeam->currentStable)->not->toBeNull()->id->toBe($stable->id);
            expect($secondTagTeam->currentStable)->not->toBeNull()->id->toBe($stable->id);

            // Verify stable has both tag teams
            expect($stable->currentTagTeams()->count())->toBe(2);

            // Verify total pivot record count
            $tagTeamCount = StableTagTeam::where('stable_id', $stable->id)
                ->whereNull('left_at')
                ->count();
            expect($tagTeamCount)->toBe(2);
        });

        test('tag team can be part of multiple stables across different time periods', function () {
            ['stable' => $stable, 'tagTeam' => $tagTeam, 'secondStable' => $secondStable] = stablesStableTagTeamStableFixtures();

            $firstPeriodStart = Carbon::now()->subYear();
            $firstPeriodEnd = Carbon::now()->subMonths(6);
            $secondPeriodStart = Carbon::now()->subMonths(3);

            // First stable membership (completed)
            $tagTeam->stables()->attach($stable->id, [
                'joined_at' => $firstPeriodStart,
                'left_at' => $firstPeriodEnd,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Second stable membership (current)
            $tagTeam->stables()->attach($secondStable->id, [
                'joined_at' => $secondPeriodStart,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Verify relationship counts
            expect($tagTeam->stables()->count())->toBe(2);
            expect($tagTeam->currentStable)->not()->toBeNull()
                ->and($tagTeam->previousStables()->count())->toBe(1);

            // Verify current stable is correct
            $currentStable = requiredModel($tagTeam->currentStable);
            expect($currentStable->id)->toBe($secondStable->id);
            $currentMembership = StableTagTeam::query()
                ->whereBelongsTo($currentStable)
                ->whereBelongsTo($tagTeam, 'tagTeam')
                ->firstOrFail();
            expect($currentMembership->joined_at->format('Y-m-d H:i:s'))->toBe($secondPeriodStart->format('Y-m-d H:i:s'))
                ->and($currentMembership->left_at)->toBeNull();

            // Verify previous stable is correct
            $previousStable = $tagTeam->previousStables()->firstOrFail();
            expect($previousStable->id)->toBe($stable->id)
                ->and(Carbon::parse($previousStable->pivot->joined_at)->format('Y-m-d H:i:s'))->toBe($firstPeriodStart->format('Y-m-d H:i:s'))
                ->and(Carbon::parse($previousStable->pivot->left_at)->format('Y-m-d H:i:s'))->toBe($firstPeriodEnd->format('Y-m-d H:i:s'));
        });
    });

    describe('TagTeam-Stable Membership Termination', function () {
        test('tag team leaving stable updates pivot correctly', function () {
            ['stable' => $stable, 'tagTeam' => $tagTeam] = stablesStableTagTeamStableFixtures();
            stablesStableTagTeamTagTeamSetupFixtures($tagTeam, $stable);

            $leaveDate = Carbon::now();

            // End the relationship by updating the pivot
            $tagTeam->stables()->updateExistingPivot($stable->id, [
                'left_at' => $leaveDate,
                'updated_at' => now(),
            ]);

            // Verify relationship status changed
            expect($tagTeam->currentStable)->toBeNull();
            expect($tagTeam->previousStables()->count())->toBe(1);

            // Verify pivot data is updated
            $previousStable = $tagTeam->previousStables()->firstOrFail();
            expect(Carbon::parse($previousStable->pivot->left_at)->format('Y-m-d H:i:s'))->toBe($leaveDate->format('Y-m-d H:i:s'));
        });

        test('detaching tag team completely removes relationship', function () {
            ['stable' => $stable, 'tagTeam' => $tagTeam] = stablesStableTagTeamStableFixtures();
            stablesStableTagTeamTagTeamSetupFixtures($tagTeam, $stable);

            // Detach the tag team from stable
            $tagTeam->stables()->detach($stable->id);

            // Verify all relationships are gone
            expect($tagTeam->stables()->count())->toBe(0);
            expect($tagTeam->currentStable)->toBeNull()
                ->and($tagTeam->previousStables()->count())->toBe(0);

            // Verify pivot record is deleted
            expect(StableTagTeam::where('tag_team_id', $tagTeam->id)
                ->where('stable_id', $stable->id)
                ->exists())->toBeFalse();
        });
    });

    describe('StableTagTeam Pivot Model Direct Queries', function () {
        test('StableTagTeam pivot model can be queried directly', function () {
            ['stable' => $stable, 'tagTeam' => $tagTeam] = stablesStableTagTeamStableFixtures();

            $tagTeam->stables()->attach($stable->id, [
                'joined_at' => Carbon::now()->subMonths(4),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $pivotRecord = StableTagTeam::where('tag_team_id', $tagTeam->id)
                ->where('stable_id', $stable->id)
                ->firstOrFail();

            expect($pivotRecord->tag_team_id)->toBe($tagTeam->id)
                ->and($pivotRecord->stable_id)->toBe($stable->id)
                ->and($pivotRecord->joined_at)->toBeInstanceOf(Carbon::class)
                ->and($pivotRecord->left_at)->toBeNull();

            // Test pivot relationships
            expect($pivotRecord->tagTeam?->id)->toBe($tagTeam->id);
            expect($pivotRecord->stable->id)->toBe($stable->id);
        });

        test('pivot model handles date casting correctly', function () {
            ['stable' => $stable, 'tagTeam' => $tagTeam] = stablesStableTagTeamStableFixtures();

            $joinedDate = Carbon::now()->subMonths(4);
            $leftDate = Carbon::now()->subMonths(1);

            // Test tag team pivot
            $tagTeam->stables()->attach($stable->id, [
                'joined_at' => $joinedDate,
                'left_at' => $leftDate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $tagTeamPivot = StableTagTeam::where('tag_team_id', $tagTeam->id)
                ->where('stable_id', $stable->id)
                ->firstOrFail();

            expect($tagTeamPivot->joined_at)->toBeInstanceOf(Carbon::class)
                ->and($tagTeamPivot->left_at)->toBeInstanceOf(Carbon::class)
                ->and($tagTeamPivot->joined_at->format('Y-m-d H:i:s'))->toBe($joinedDate->format('Y-m-d H:i:s'))
                ->and(requiredDate($tagTeamPivot->left_at)->format('Y-m-d H:i:s'))->toBe($leftDate->format('Y-m-d H:i:s'));
        });
    });

    describe('TagTeam Stable Queries', function () {
        test('current stable query returns only active relationship', function () {
            ['stable' => $stable, 'tagTeam' => $tagTeam, 'secondStable' => $secondStable] = stablesStableTagTeamStableFixtures();
            stablesStableTagTeamTagTeamSetupFixtures2($tagTeam, $stable, $secondStable);

            $currentStable = requiredModel($tagTeam->currentStable);

            expect($currentStable->id)->toBe($secondStable->id);
            $membership = StableTagTeam::query()
                ->whereBelongsTo($currentStable)
                ->whereBelongsTo($tagTeam, 'tagTeam')
                ->firstOrFail();
            expect($membership->left_at)->toBeNull();
        });

        test('previous stables query returns only completed relationships', function () {
            ['stable' => $stable, 'tagTeam' => $tagTeam, 'secondStable' => $secondStable] = stablesStableTagTeamStableFixtures();
            stablesStableTagTeamTagTeamSetupFixtures2($tagTeam, $stable, $secondStable);

            $previousStables = $tagTeam->previousStables()->get();

            expect($previousStables)->toHaveCount(1)
                ->and($previousStables->firstOrFail()->id)->toBe($stable->id);
            $membership = StableTagTeam::query()
                ->whereBelongsTo($stable)
                ->whereBelongsTo($tagTeam, 'tagTeam')
                ->firstOrFail();
            expect($membership->left_at)->not->toBeNull();
        });

        test('all stables query returns complete membership history', function () {
            ['stable' => $stable, 'tagTeam' => $tagTeam, 'secondStable' => $secondStable] = stablesStableTagTeamStableFixtures();
            stablesStableTagTeamTagTeamSetupFixtures2($tagTeam, $stable, $secondStable);

            $allStables = $tagTeam->stables()->get();

            expect($allStables)->toHaveCount(2);

            $stableIds = $allStables->pluck('id')->toArray();
            expect($stableIds)->toContain($stable->id)
                ->toContain($secondStable->id);
        });

    });
});
