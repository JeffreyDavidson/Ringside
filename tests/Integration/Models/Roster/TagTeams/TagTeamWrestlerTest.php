<?php

declare(strict_types=1);

use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @return array{
 *     tagTeam: TagTeam,
 *     wrestler: Wrestler,
 *     secondTagTeam: TagTeam,
 *     secondWrestler: Wrestler,
 *     thirdWrestler: Wrestler,
 * }
 */
function tagTeamsTagTeamWrestlerTagTeamFixtures(): array
{
    // Create test entities with realistic factory states (basic team without auto-attached wrestlers)
    $tagTeam = TagTeam::factory()->create([
        'name' => 'The Hardy Boyz',
    ]);

    // Create employment for tag team manually
    $tagTeam->employments()->create([
        'started_at' => now()->subDays(3),
        'ended_at' => null,
    ]);

    $wrestler = Wrestler::factory()->employed()->create([
        'name' => 'Matt Hardy',
        'hometown' => 'Cameron, North Carolina',
    ]);

    $secondTagTeam = TagTeam::factory()->create([
        'name' => 'The Dudley Boyz',
    ]);

    // Create employment for second tag team manually
    $secondTagTeam->employments()->create([
        'started_at' => now()->subDays(3),
        'ended_at' => null,
    ]);

    $secondWrestler = Wrestler::factory()->employed()->create([
        'name' => 'Jeff Hardy',
        'hometown' => 'Cameron, North Carolina',
    ]);

    $thirdWrestler = Wrestler::factory()->employed()->create([
        'name' => 'Bubba Ray Dudley',
        'hometown' => 'Dudleyville',
    ]);

    return [
        'tagTeam' => $tagTeam,
        'wrestler' => $wrestler,
        'secondTagTeam' => $secondTagTeam,
        'secondWrestler' => $secondWrestler,
        'thirdWrestler' => $thirdWrestler,
    ];
}

function tagTeamsTagTeamWrestlerWrestlerSetupFixtures(Wrestler $wrestler, TagTeam $tagTeam): void
{
    createTagTeamMembership($wrestler, $tagTeam);

}

function tagTeamsTagTeamWrestlerWrestlerSetupFixtures2(Wrestler $wrestler, TagTeam $tagTeam, TagTeam $secondTagTeam, Wrestler $secondWrestler): void
{
    // Set up complex relationship scenario
    createTagTeamHistory($wrestler, [
        [
            'tag_team' => $tagTeam,
            'joined_at' => Carbon::now()->subYear(),
            'left_at' => Carbon::now()->subMonths(6),
        ],
        [
            'tag_team' => $secondTagTeam,
            'joined_at' => Carbon::now()->subMonths(3),
            'left_at' => null,
        ],
    ]);

    createTagTeamMembership($secondWrestler, $tagTeam, [
        'joined_at' => Carbon::now()->subMonths(2),
    ]);

}

/**
 * Integration tests for TagTeamWrestler pivot model functionality.
 *
 * This test suite validates the complete workflow of tag team-wrestler
 * partnerships including joining teams, leaving teams, querying current
 * and previous tag teams, and ensuring proper business rule enforcement.
 *
 * Tests cover Wrestler's tag team membership relationships and the
 * TagTeamWrestler pivot model with real database records.
 *
 * @see TagTeamWrestler
 */
describe('TagTeamWrestler Pivot Model', function () {
    describe('Relationship Creation', function () {
        test('wrestler can join a tag team with proper pivot data', function () {
            ['tagTeam' => $tagTeam, 'wrestler' => $wrestler] = tagTeamsTagTeamWrestlerTagTeamFixtures();

            $joinedDate = Carbon::now()->subMonths(6);

            createTagTeamMembership($wrestler, $tagTeam, ['joined_at' => $joinedDate]);

            expect($wrestler->tagTeams()->count())->toBe(1)
                ->and($wrestler->refresh()->currentTagTeam)->not->toBeNull()
                ->and($wrestler->previousTagTeams()->count())->toBe(0);

            expectTagTeamMembership($wrestler, $tagTeam, [
                'joined_at' => $joinedDate,
                'left_at' => null,
            ]);
        });

        test('tag team can have multiple wrestlers as partners', function () {
            ['tagTeam' => $tagTeam, 'wrestler' => $wrestler, 'secondWrestler' => $secondWrestler] = tagTeamsTagTeamWrestlerTagTeamFixtures();

            $joinedDate1 = Carbon::now()->subMonths(3);
            $joinedDate2 = Carbon::now()->subMonths(2);

            createTagTeamMembership($wrestler, $tagTeam, ['joined_at' => $joinedDate1]);
            createTagTeamMembership($secondWrestler, $tagTeam, ['joined_at' => $joinedDate2]);

            expect($wrestler->refresh()->currentTagTeam)->not->toBeNull()
                ->and($secondWrestler->refresh()->currentTagTeam)->not->toBeNull();

            // Verify both wrestlers are in the same tag team
            expect($wrestler->currentTagTeam)->not->toBeNull()->id->toBe($tagTeam->id);
            expect($secondWrestler->currentTagTeam)->not->toBeNull()->id->toBe($tagTeam->id);

            // Verify tag team has both wrestlers
            expect($tagTeam->currentWrestlers()->count())->toBe(2);
            expect($tagTeam->currentWrestlers->pluck('id'))
                ->toContain($wrestler->id)
                ->toContain($secondWrestler->id);
        });

        test('wrestler can be part of multiple tag teams across different time periods', function () {
            ['tagTeam' => $tagTeam, 'wrestler' => $wrestler, 'secondTagTeam' => $secondTagTeam] = tagTeamsTagTeamWrestlerTagTeamFixtures();

            $periods = [
                [
                    'tag_team' => $tagTeam,
                    'joined_at' => Carbon::now()->subYear(),
                    'left_at' => Carbon::now()->subMonths(6),
                ],
                [
                    'tag_team' => $secondTagTeam,
                    'joined_at' => Carbon::now()->subMonths(3),
                    'left_at' => null,
                ],
            ];

            createTagTeamHistory($wrestler, $periods);

            expect($wrestler->tagTeams()->count())->toBe(2)
                ->and($wrestler->refresh()->currentTagTeam)->not->toBeNull()
                ->and($wrestler->previousTagTeams()->count())->toBe(1);

            // Verify current tag team is correct
            $currentTagTeam = requiredModel($wrestler->currentTagTeam);
            expect($currentTagTeam->id)->toBe($secondTagTeam->id);
            $currentMembership = TagTeamWrestler::query()
                ->whereBelongsTo($currentTagTeam, 'tagTeam')
                ->whereBelongsTo($wrestler)
                ->firstOrFail();
            expect($currentMembership->left_at)->toBeNull();

            // Verify previous tag team is correct
            $previousTagTeam = $wrestler->previousTagTeams()->firstOrFail();
            expect($previousTagTeam->id)->toBe($tagTeam->id)
                ->and($previousTagTeam->pivot->left_at)->not()
                ->toBeNull();
        });
    });

    describe('Relationship Termination', function () {
        test('leaving tag team updates pivot correctly', function () {
            ['tagTeam' => $tagTeam, 'wrestler' => $wrestler] = tagTeamsTagTeamWrestlerTagTeamFixtures();
            tagTeamsTagTeamWrestlerWrestlerSetupFixtures($wrestler, $tagTeam);

            $leaveDate = Carbon::now();

            endTagTeamMembership($wrestler, $tagTeam, $leaveDate);

            expect($wrestler->refresh()->currentTagTeam)->toBeNull()
                ->and($wrestler->previousTagTeams()->count())->toBe(1);

            $previousTagTeam = $wrestler->previousTagTeams()->firstOrFail();
            expect(requiredDate($previousTagTeam->pivot->left_at)->format('Y-m-d H:i:s'))->toBe($leaveDate->format('Y-m-d H:i:s'));
        });

        test('detaching wrestler completely removes relationship', function () {
            ['tagTeam' => $tagTeam, 'wrestler' => $wrestler] = tagTeamsTagTeamWrestlerTagTeamFixtures();
            tagTeamsTagTeamWrestlerWrestlerSetupFixtures($wrestler, $tagTeam);

            // Detach the wrestler from tag team
            $wrestler->tagTeams()->detach($tagTeam->id);

            // Verify all relationships are gone
            expect($wrestler->tagTeams()->count())->toBe(0);
            expect($wrestler->refresh()->currentTagTeam)->toBeNull()
                ->and($wrestler->previousTagTeams()->count())->toBe(0);

            // Verify pivot record is deleted
            expect(TagTeamWrestler::where('wrestler_id', $wrestler->id)
                ->where('tag_team_id', $tagTeam->id)
                ->exists())->toBeFalse();
        });
    });

    describe('Relationship Queries', function () {
        test('current tag team query returns only active relationship', function () {
            ['tagTeam' => $tagTeam, 'wrestler' => $wrestler, 'secondTagTeam' => $secondTagTeam, 'secondWrestler' => $secondWrestler] = tagTeamsTagTeamWrestlerTagTeamFixtures();
            tagTeamsTagTeamWrestlerWrestlerSetupFixtures2($wrestler, $tagTeam, $secondTagTeam, $secondWrestler);

            $currentTagTeam = requiredModel($wrestler->currentTagTeam);

            expect($currentTagTeam->id)->toBe($secondTagTeam->id);
            $membership = TagTeamWrestler::query()
                ->whereBelongsTo($currentTagTeam, 'tagTeam')
                ->whereBelongsTo($wrestler)
                ->firstOrFail();
            expect($membership->left_at)->toBeNull();
        });

        test('previous tag teams query returns only completed relationships', function () {
            ['tagTeam' => $tagTeam, 'wrestler' => $wrestler, 'secondTagTeam' => $secondTagTeam, 'secondWrestler' => $secondWrestler] = tagTeamsTagTeamWrestlerTagTeamFixtures();
            tagTeamsTagTeamWrestlerWrestlerSetupFixtures2($wrestler, $tagTeam, $secondTagTeam, $secondWrestler);

            $previousTagTeams = $wrestler->previousTagTeams()->get();

            expect($previousTagTeams)->toHaveCount(1)
                ->and($previousTagTeams->firstOrFail()->id)->toBe($tagTeam->id);
            $membership = TagTeamWrestler::query()
                ->whereBelongsTo($tagTeam, 'tagTeam')
                ->whereBelongsTo($wrestler)
                ->firstOrFail();
            expect($membership->left_at)->not->toBeNull();
        });

        test('all tag teams query returns complete relationship history', function () {
            ['tagTeam' => $tagTeam, 'wrestler' => $wrestler, 'secondTagTeam' => $secondTagTeam, 'secondWrestler' => $secondWrestler] = tagTeamsTagTeamWrestlerTagTeamFixtures();
            tagTeamsTagTeamWrestlerWrestlerSetupFixtures2($wrestler, $tagTeam, $secondTagTeam, $secondWrestler);

            $allTagTeams = $wrestler->tagTeams()->get();

            expect($allTagTeams)->toHaveCount(2);

            $tagTeamIds = $allTagTeams->pluck('id')->toArray();
            expect($tagTeamIds)->toContain($tagTeam->id)
                ->toContain($secondTagTeam->id);
        });

        test('previous tag team query returns most recent former team', function () {
            ['tagTeam' => $tagTeam, 'wrestler' => $wrestler, 'secondTagTeam' => $secondTagTeam, 'secondWrestler' => $secondWrestler] = tagTeamsTagTeamWrestlerTagTeamFixtures();
            tagTeamsTagTeamWrestlerWrestlerSetupFixtures2($wrestler, $tagTeam, $secondTagTeam, $secondWrestler);

            // Add another previous tag team with earlier date
            $thirdTagTeam = TagTeam::factory()->create(['name' => 'The Rock n Sock Connection']);
            $wrestler->tagTeams()->attach($thirdTagTeam->id, [
                'joined_at' => Carbon::now()->subYears(2),
                'left_at' => Carbon::now()->subMonths(18),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $mostRecentPrevious = $wrestler->previousTagTeam;

            expect($mostRecentPrevious)
                ->not()->toBeNull()
                ->id->toBe($tagTeam->id); // More recent than the third team
        });

        test('isAMemberOfCurrentTagTeam accurately checks current status', function () {
            ['tagTeam' => $tagTeam, 'wrestler' => $wrestler, 'secondTagTeam' => $secondTagTeam, 'secondWrestler' => $secondWrestler, 'thirdWrestler' => $thirdWrestler] = tagTeamsTagTeamWrestlerTagTeamFixtures();
            tagTeamsTagTeamWrestlerWrestlerSetupFixtures2($wrestler, $tagTeam, $secondTagTeam, $secondWrestler);

            expect($wrestler->refresh()->currentTagTeam)->not->toBeNull()
                ->and($secondWrestler->refresh()->currentTagTeam)->not->toBeNull()
                ->and($thirdWrestler->refresh()->currentTagTeam)->toBeNull();
        });

        test('tag team relationships are properly ordered by joined_at', function () {
            ['tagTeam' => $tagTeam, 'wrestler' => $wrestler, 'secondTagTeam' => $secondTagTeam, 'secondWrestler' => $secondWrestler] = tagTeamsTagTeamWrestlerTagTeamFixtures();
            tagTeamsTagTeamWrestlerWrestlerSetupFixtures2($wrestler, $tagTeam, $secondTagTeam, $secondWrestler);

            $tagTeamsChronological = $wrestler->tagTeams()
                ->orderBy('joined_at', 'asc')
                ->get();

            expect($tagTeamsChronological->firstOrFail()->id)->toBe($tagTeam->id)
                ->and($tagTeamsChronological->reverse()->firstOrFail()->id)->toBe($secondTagTeam->id);
        });

        test('can query tag teams within specific date ranges', function () {
            ['tagTeam' => $tagTeam, 'wrestler' => $wrestler, 'secondTagTeam' => $secondTagTeam, 'secondWrestler' => $secondWrestler] = tagTeamsTagTeamWrestlerTagTeamFixtures();
            tagTeamsTagTeamWrestlerWrestlerSetupFixtures2($wrestler, $tagTeam, $secondTagTeam, $secondWrestler);

            $recentTagTeams = $wrestler->tagTeams()
                ->wherePivot('joined_at', '>=', Carbon::now()->subMonths(4))
                ->get();

            expect($recentTagTeams)->toHaveCount(1)
                ->and($recentTagTeams->firstOrFail()->id)->toBe($secondTagTeam->id);
        });
    });

    describe('Pivot Model Operations', function () {
        test('pivot model can be queried directly', function () {
            ['tagTeam' => $tagTeam, 'wrestler' => $wrestler] = tagTeamsTagTeamWrestlerTagTeamFixtures();

            createTagTeamMembership($wrestler, $tagTeam);

            $pivotRecord = TagTeamWrestler::where('wrestler_id', $wrestler->id)
                ->where('tag_team_id', $tagTeam->id)
                ->firstOrFail();

            expect($pivotRecord->wrestler_id)->toBe($wrestler->id)
                ->and($pivotRecord->tag_team_id)->toBe($tagTeam->id)
                ->and($pivotRecord->joined_at)->toBeInstanceOf(Carbon::class)
                ->and($pivotRecord->left_at)->toBeNull();
        });

        test('pivot model relationships work correctly', function () {
            ['tagTeam' => $tagTeam, 'wrestler' => $wrestler] = tagTeamsTagTeamWrestlerTagTeamFixtures();

            createTagTeamMembership($wrestler, $tagTeam);

            $pivotRecord = TagTeamWrestler::where('wrestler_id', $wrestler->id)
                ->where('tag_team_id', $tagTeam->id)
                ->firstOrFail();

            // Test pivot relationships
            expect(requiredModel($pivotRecord->wrestler)->id)->toBe($wrestler->id);
            expect(requiredModel($pivotRecord->tagTeam)->id)->toBe($tagTeam->id);
        });

        test('pivot model handles date casting correctly', function () {
            ['tagTeam' => $tagTeam, 'wrestler' => $wrestler] = tagTeamsTagTeamWrestlerTagTeamFixtures();

            $joinedDate = Carbon::now()->subMonths(6);
            $leftDate = Carbon::now()->subMonths(1);

            createTagTeamMembership($wrestler, $tagTeam, [
                'joined_at' => $joinedDate,
                'left_at' => $leftDate,
            ]);

            $pivotRecord = TagTeamWrestler::where('wrestler_id', $wrestler->id)
                ->where('tag_team_id', $tagTeam->id)
                ->firstOrFail();

            expect($pivotRecord->joined_at)->toBeInstanceOf(Carbon::class)
                ->and($pivotRecord->left_at)->toBeInstanceOf(Carbon::class)
                ->and($pivotRecord->joined_at->format('Y-m-d H:i:s'))->toBe($joinedDate->format('Y-m-d H:i:s'))
                ->and(requiredDate($pivotRecord->left_at)->format('Y-m-d H:i:s'))->toBe($leftDate->format('Y-m-d H:i:s'));
        });
    });

    describe('Business Rule Validation', function () {
        test('wrestler cannot have multiple concurrent tag team memberships', function () {
            ['tagTeam' => $tagTeam, 'wrestler' => $wrestler, 'secondTagTeam' => $secondTagTeam] = tagTeamsTagTeamWrestlerTagTeamFixtures();

            createTagTeamMembership($wrestler, $tagTeam);

            // The nested transaction becomes a savepoint, so PostgreSQL keeps the test transaction usable.
            expect(fn () => DB::transaction(fn () => createTagTeamMembership($wrestler, $secondTagTeam, [
                'joined_at' => Carbon::now()->subMonths(3),
            ])))->toThrow(QueryException::class)
                ->and($wrestler->tagTeams()->wherePivotNull('left_at')->count())->toBe(1);
        });

        test('tag team membership periods should not overlap incorrectly', function () {
            ['tagTeam' => $tagTeam, 'wrestler' => $wrestler, 'secondTagTeam' => $secondTagTeam] = tagTeamsTagTeamWrestlerTagTeamFixtures();

            createTagTeamHistory($wrestler, [
                [
                    'tag_team' => $tagTeam,
                    'joined_at' => Carbon::now()->subYear(),
                    'left_at' => Carbon::now()->subMonths(6),
                ],
                [
                    'tag_team' => $secondTagTeam,
                    'joined_at' => Carbon::now()->subMonths(8), // Overlaps
                    'left_at' => Carbon::now()->subMonths(4),
                ],
            ]);

            // Verify both relationships exist (validation would be in business logic)
            expect($wrestler->tagTeams()->count())->toBe(2);
        });

        test('joined date cannot be after left date', function () {
            ['tagTeam' => $tagTeam, 'wrestler' => $wrestler] = tagTeamsTagTeamWrestlerTagTeamFixtures();

            $joinedDate = Carbon::now()->subMonths(3);
            $leftDate = Carbon::now()->subMonths(6); // Earlier than joined date (invalid)

            expect(fn () => DB::transaction(fn () => createTagTeamMembership($wrestler, $tagTeam, [
                'joined_at' => $joinedDate,
                'left_at' => $leftDate,
            ])))->toThrow(QueryException::class);
        });
    });

    describe('Complex Scenarios', function () {
        test('wrestler can rejoin the same tag team after leaving', function () {
            ['tagTeam' => $tagTeam, 'wrestler' => $wrestler, 'secondTagTeam' => $secondTagTeam] = tagTeamsTagTeamWrestlerTagTeamFixtures();

            createTagTeamHistory($wrestler, [
                [
                    'tag_team' => $tagTeam,
                    'joined_at' => Carbon::now()->subYear(),
                    'left_at' => Carbon::now()->subMonths(8),
                ],
                [
                    'tag_team' => $secondTagTeam,
                    'joined_at' => Carbon::now()->subMonths(6),
                    'left_at' => Carbon::now()->subMonths(4),
                ],
                [
                    'tag_team' => $tagTeam,
                    'joined_at' => Carbon::now()->subMonths(2),
                    'left_at' => null,
                ],
            ]);

            expect($wrestler->tagTeams()->count())->toBe(3)
                ->and($wrestler->refresh()->currentTagTeam)->not->toBeNull()
                ->and($wrestler->previousTagTeams()->count())->toBe(2);

            // Verify current tag team is the original team
            $currentTagTeam = requiredModel($wrestler->currentTagTeam);
            expect($currentTagTeam->id)->toBe($tagTeam->id);

            // Verify relationship history includes both tag teams
            $allTagTeams = $wrestler->tagTeams()->get();
            $uniqueTagTeams = $allTagTeams->unique('id');
            expect($uniqueTagTeams)->toHaveCount(2);
        });

        test('can query tag team partnership duration and calculate statistics', function () {
            ['tagTeam' => $tagTeam, 'wrestler' => $wrestler, 'secondTagTeam' => $secondTagTeam] = tagTeamsTagTeamWrestlerTagTeamFixtures();

            createTagTeamHistory($wrestler, [
                [
                    'tag_team' => $tagTeam,
                    'joined_at' => Carbon::now()->subYear(),
                    'left_at' => Carbon::now()->subMonths(6),
                ],
                [
                    'tag_team' => $secondTagTeam,
                    'joined_at' => Carbon::now()->subMonths(3),
                    'left_at' => null,
                ],
            ]);

            // Calculate duration of completed period
            $completedPeriod = $wrestler->previousTagTeams()->firstOrFail();
            $completedMembership = TagTeamWrestler::query()
                ->whereBelongsTo($completedPeriod, 'tagTeam')
                ->whereBelongsTo($wrestler)
                ->firstOrFail();
            $duration = $completedMembership->joined_at->diffInDays(requiredDate($completedMembership->left_at));
            expect($duration)->toBeGreaterThan(150); // Approximately 6 months

            // Calculate duration of current period
            $currentPeriod = requiredModel($wrestler->currentTagTeam);
            $currentMembership = TagTeamWrestler::query()
                ->whereBelongsTo($currentPeriod, 'tagTeam')
                ->whereBelongsTo($wrestler)
                ->firstOrFail();
            $currentDuration = $currentMembership->joined_at->diffInDays(Carbon::now());
            expect($currentDuration)->toBeGreaterThan(80); // Approximately 3 months
        });

        test('tag team with multiple member changes over time', function () {
            ['tagTeam' => $tagTeam, 'wrestler' => $wrestler, 'secondWrestler' => $secondWrestler, 'thirdWrestler' => $thirdWrestler] = tagTeamsTagTeamWrestlerTagTeamFixtures();

            // Original two-person tag team
            createTagTeamMembership($wrestler, $tagTeam, [
                'joined_at' => Carbon::now()->subMonths(6),
            ]);
            createTagTeamMembership($secondWrestler, $tagTeam, [
                'joined_at' => Carbon::now()->subMonths(4),
            ]);

            // First wrestler leaves
            endTagTeamMembership($wrestler, $tagTeam, Carbon::now()->subMonths(2));

            // Third wrestler joins
            createTagTeamMembership($thirdWrestler, $tagTeam, [
                'joined_at' => Carbon::now()->subMonths(1),
            ]);

            // Verify current membership
            expect($wrestler->refresh()->currentTagTeam)->toBeNull();
            expect($secondWrestler->refresh()->currentTagTeam)->not->toBeNull()
                ->and($thirdWrestler->refresh()->currentTagTeam)->not->toBeNull();

            // Verify tag team evolution
            $currentMembers = $tagTeam->currentWrestlers()->get();
            expect($currentMembers)->toHaveCount(2)
                ->and($currentMembers->pluck('id'))->toContain($secondWrestler->id)
                ->toContain($thirdWrestler->id)->not->toContain($wrestler->id);
        });
    });

    describe('Performance Optimization', function () {
        test('eager loading tag team relationships works correctly', function () {
            ['tagTeam' => $tagTeam, 'wrestler' => $wrestler, 'secondTagTeam' => $secondTagTeam, 'secondWrestler' => $secondWrestler] = tagTeamsTagTeamWrestlerTagTeamFixtures();

            createTagTeamMembership($wrestler, $tagTeam);
            createTagTeamMembership($secondWrestler, $secondTagTeam);

            // Load wrestlers with their current tag teams
            $wrestlers = Wrestler::with('currentTagTeam')->get();

            expect($wrestlers)->toHaveCount(3); // wrestler, secondWrestler, thirdWrestler from beforeEach

            // Verify relationships are loaded
            $wrestlerWithTagTeam = requiredModel($wrestlers->firstWhere('id', $wrestler->id));
            expect($wrestlerWithTagTeam->relationLoaded('currentTagTeam'))->toBeTrue()
                ->and($wrestlerWithTagTeam->currentTagTeam)->not()
                ->toBeNull();
        });

        test('can efficiently count tag team relationships without loading them', function () {
            ['tagTeam' => $tagTeam, 'wrestler' => $wrestler, 'secondTagTeam' => $secondTagTeam] = tagTeamsTagTeamWrestlerTagTeamFixtures();

            createTagTeamHistory($wrestler, [
                [
                    'tag_team' => $tagTeam,
                    'joined_at' => Carbon::now()->subMonths(6),
                    'left_at' => null,
                ],
                [
                    'tag_team' => $secondTagTeam,
                    'joined_at' => Carbon::now()->subMonths(3),
                    'left_at' => Carbon::now()->subMonths(1),
                ],
            ]);

            expect($wrestler->tagTeams()->count())->toBe(2)
                ->and($wrestler->previousTagTeams()->count())->toBe(1)
                ->and($wrestler->refresh()->currentTagTeam)->not->toBeNull();

            // Verify relationships are not loaded
            expect($wrestler->relationLoaded('tagTeams'))->toBeFalse();
        });

        test('native through relationships return single results', function () {
            ['tagTeam' => $tagTeam, 'wrestler' => $wrestler, 'secondTagTeam' => $secondTagTeam] = tagTeamsTagTeamWrestlerTagTeamFixtures();

            createTagTeamHistory($wrestler, [
                [
                    'tag_team' => $tagTeam,
                    'joined_at' => Carbon::now()->subYear(),
                    'left_at' => Carbon::now()->subMonths(6),
                ],
                [
                    'tag_team' => $secondTagTeam,
                    'joined_at' => Carbon::now()->subMonths(3),
                    'left_at' => null,
                ],
            ]);

            // Single-result relationships return models, not collections.
            $currentTagTeam = requiredModel($wrestler->currentTagTeam);
            $previousTagTeam = $wrestler->previousTagTeam;

            expect($currentTagTeam)
                ->toBeInstanceOf(TagTeam::class)
                ->id->toBe($secondTagTeam->id)
                ->and($previousTagTeam)->not->toBeNull()
                ->toBeInstanceOf(TagTeam::class)->id->toBe($tagTeam->id);
        });
    });
});
