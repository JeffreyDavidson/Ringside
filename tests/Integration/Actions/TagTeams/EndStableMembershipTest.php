<?php

declare(strict_types=1);

use App\Actions\TagTeams\DeleteAction;
use App\Actions\TagTeams\ReleaseAction;
use App\Actions\TagTeams\RetireAction;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;

use function Pest\Laravel\assertDatabaseHas;

dataset('actions that end a tag team', [
    'release' => [ReleaseAction::class, 'employed'],
    'retire' => [RetireAction::class, 'employed'],
    'delete' => [DeleteAction::class, 'unemployed'],
]);

test('it ends the stable membership of a tag team that leaves', function (string $action, string $state) {
    // Arrange
    $tagTeam = TagTeam::factory()->{$state}()->create();
    $stable = Stable::factory()->create();
    $tagTeam->stables()->attach($stable, ['joined_at' => now()->subMonth()]);

    // Act
    resolve($action)
        ->handle($tagTeam, now());

    // Assert
    assertDatabaseHas('stables_tag_teams', [
        'stable_id' => $stable->id,
        'tag_team_id' => $tagTeam->id,
        'left_at' => now()->toDateTimeString(),
    ]);
    expect($stable->currentTagTeams()->count())->toBe(0);
})->with('actions that end a tag team');

test('it leaves an ended membership and the memberships of other teams untouched', function (string $action, string $state) {
    // Arrange
    $tagTeam = TagTeam::factory()->{$state}()->create();
    $stable = Stable::factory()->create();
    $endedAt = now()->subWeek();
    $tagTeam->stables()->attach($stable, ['joined_at' => now()->subMonth(), 'left_at' => $endedAt]);
    $otherTagTeam = TagTeam::factory()->employed()->create();
    $otherTagTeam->stables()->attach($stable, ['joined_at' => now()->subMonth()]);

    // Act
    resolve($action)
        ->handle($tagTeam, now());

    // Assert
    assertDatabaseHas('stables_tag_teams', [
        'stable_id' => $stable->id,
        'tag_team_id' => $tagTeam->id,
        'left_at' => $endedAt->toDateTimeString(),
    ]);
    expect($tagTeam->stables()->wherePivotNull('left_at')->count())->toBe(0)
        ->and($stable->currentTagTeams()->pluck('tag_teams.id')->all())->toBe([$otherTagTeam->id]);
})->with('actions that end a tag team');
