<?php

declare(strict_types=1);

use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\DB;

describe('the current tag team membership migration', function () {
    beforeEach(function () {
        dropEnforcingIndex('tag_teams_wrestlers', CURRENT_TAG_TEAM_MEMBERSHIP_INDEX, 'current_wrestler_id');
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
