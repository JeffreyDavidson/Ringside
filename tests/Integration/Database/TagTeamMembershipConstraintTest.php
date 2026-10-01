<?php

declare(strict_types=1);

use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

const CURRENT_TAG_TEAM_MEMBERSHIP_INDEX = 'tag_teams_wrestlers_one_current_membership_unique';

function currentTagTeamMembershipIndexExists(): bool
{
    return collect(Schema::getIndexes('tag_teams_wrestlers'))->contains('name', CURRENT_TAG_TEAM_MEMBERSHIP_INDEX);
}

function joinTagTeam(TagTeam $tagTeam, Wrestler $wrestler, ?DateTimeInterface $leftAt = null): TagTeamWrestler
{
    return TagTeamWrestler::query()->create([
        'tag_team_id' => $tagTeam->id,
        'wrestler_id' => $wrestler->id,
        'joined_at' => now()->subMonth(),
        'left_at' => $leftAt,
    ]);
}

test('the current tag team membership index exists', function () {
    expect(currentTagTeamMembershipIndexExists())->toBeTrue();
});

test('a wrestler may have multiple ended tag team memberships', function () {
    $wrestler = Wrestler::factory()->create();

    TagTeam::factory()->count(2)->create()->each(
        fn (TagTeam $tagTeam) => joinTagTeam($tagTeam, $wrestler, now()->subDay())
    );

    expect(TagTeamWrestler::query()->where('wrestler_id', $wrestler->id)->count())->toBe(2);
});

test('a wrestler may have an ended membership and one current membership', function () {
    $wrestler = Wrestler::factory()->create();
    [$formerTagTeam, $currentTagTeam] = TagTeam::factory()->count(2)->create()->all();

    joinTagTeam($formerTagTeam, $wrestler, now()->subDay());
    joinTagTeam($currentTagTeam, $wrestler);

    expect(TagTeamWrestler::query()->current()->where('wrestler_id', $wrestler->id)->count())->toBe(1);
});

test('a wrestler cannot have multiple current tag team memberships', function () {
    $wrestler = Wrestler::factory()->create();
    [$firstTagTeam, $secondTagTeam] = TagTeam::factory()->count(2)->create()->all();
    joinTagTeam($firstTagTeam, $wrestler);

    // The nested transaction becomes a savepoint, so PostgreSQL keeps the surrounding
    // test transaction usable after the unique violation.
    expect(fn () => DB::transaction(fn () => joinTagTeam($secondTagTeam, $wrestler)))
        ->toThrow(QueryException::class)
        ->and(TagTeamWrestler::query()->current()->where('wrestler_id', $wrestler->id)->count())->toBe(1);
});

describe('the current tag team membership migration', function () {
    beforeEach(function () {
        DB::statement('drop index '.CURRENT_TAG_TEAM_MEMBERSHIP_INDEX);
    });

    test('it aborts before changing the schema when a wrestler has multiple current memberships', function () {
        // Arrange
        [$duplicated, $other] = Wrestler::factory()->count(2)->create()->all();
        [$firstTagTeam, $secondTagTeam, $thirdTagTeam] = TagTeam::factory()->count(3)->create()->all();
        joinTagTeam($firstTagTeam, $duplicated);
        joinTagTeam($secondTagTeam, $duplicated);
        joinTagTeam($thirdTagTeam, $duplicated, now()->subDay());
        joinTagTeam($thirdTagTeam, $other);
        $rowsBefore = DB::table('tag_teams_wrestlers')->orderBy('id')->get()->toArray();

        // Act
        $migration = require database_path('migrations/2026_10_01_162139_enforce_single_current_tag_team_membership_for_wrestlers.php');
        $migrate = fn () => $migration->up();

        // Assert
        expect($migrate)->toThrow(
            RuntimeException::class,
            "wrestler {$duplicated->id} is a current member of tag teams {$firstTagTeam->id}, {$secondTagTeam->id}. No changes were made.",
        )
            ->and(currentTagTeamMembershipIndexExists())->toBeFalse()
            ->and(DB::table('tag_teams_wrestlers')->orderBy('id')->get()->toArray())->toEqual($rowsBefore);
    });

    test('it lists every wrestler with multiple current memberships', function () {
        // Arrange
        [$firstWrestler, $secondWrestler] = Wrestler::factory()->count(2)->create()->all();
        [$firstTagTeam, $secondTagTeam] = TagTeam::factory()->count(2)->create()->all();

        foreach ([$firstWrestler, $secondWrestler] as $wrestler) {
            joinTagTeam($firstTagTeam, $wrestler);
            joinTagTeam($secondTagTeam, $wrestler);
        }

        // Act
        $migration = require database_path('migrations/2026_10_01_162139_enforce_single_current_tag_team_membership_for_wrestlers.php');
        $migrate = fn () => $migration->up();

        // Assert
        expect($migrate)->toThrow(
            RuntimeException::class,
            "wrestler {$firstWrestler->id} is a current member of tag teams {$firstTagTeam->id}, {$secondTagTeam->id}; wrestler {$secondWrestler->id} is a current member of tag teams {$firstTagTeam->id}, {$secondTagTeam->id}.",
        );
    });

    test('it creates the index once the duplicate memberships are ended', function () {
        // Arrange
        $wrestler = Wrestler::factory()->create();
        [$firstTagTeam, $secondTagTeam] = TagTeam::factory()->count(2)->create()->all();
        joinTagTeam($firstTagTeam, $wrestler);
        joinTagTeam($secondTagTeam, $wrestler);
        DB::table('tag_teams_wrestlers')->where('tag_team_id', $secondTagTeam->id)->update(['left_at' => now()]);

        // Act
        $migration = require database_path('migrations/2026_10_01_162139_enforce_single_current_tag_team_membership_for_wrestlers.php');
        $migration->up();

        // Assert
        expect(currentTagTeamMembershipIndexExists())->toBeTrue();
    });
});
