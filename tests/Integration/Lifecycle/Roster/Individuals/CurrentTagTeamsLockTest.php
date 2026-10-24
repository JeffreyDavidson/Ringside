<?php

declare(strict_types=1);

use App\Lifecycle\Roster\Individuals\CurrentTagTeamsLock;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\DB;

test('it locks the tag teams a wrestler currently belongs to and skips ended memberships', function () {
    // Arrange
    $wrestler = Wrestler::factory()->create();
    $currentTagTeam = TagTeam::factory()->create();
    $formerTagTeam = TagTeam::factory()->create();
    $wrestler->tagTeams()->attach($currentTagTeam, ['joined_at' => now()->subWeek()]);
    $wrestler->tagTeams()->attach($formerTagTeam, ['joined_at' => now()->subMonth(), 'left_at' => now()->subWeeks(2)]);

    // Act
    $locked = DB::transaction(fn () => resolve(CurrentTagTeamsLock::class)->lock($wrestler));

    // Assert
    expect($locked->pluck('id')->all())->toBe([$currentTagTeam->id]);
});

test('it locks one tag team row at a time', function () {
    // Arrange
    $tagTeam = TagTeam::factory()->create();
    $wrestler = Wrestler::factory()->create();
    $tagTeam->wrestlers()->attach($wrestler, ['joined_at' => now()->subWeek()]);

    // Act
    $statements = recordStatements(fn () => DB::transaction(fn () => resolve(CurrentTagTeamsLock::class)->lock($wrestler)));

    // Assert
    expect(lockedRowIds($statements, 'tag_teams'))->toBe([$tagTeam->id]);
});

test('it leaves out the tag team the caller already holds', function () {
    // Arrange
    $tagTeam = TagTeam::factory()->create();
    $wrestler = Wrestler::factory()->create();
    $tagTeam->wrestlers()->attach($wrestler, ['joined_at' => now()->subWeek()]);

    // Act
    $locked = DB::transaction(fn () => resolve(CurrentTagTeamsLock::class)->lock($wrestler, $tagTeam));

    // Assert
    expect($locked)->toBeEmpty();
});
