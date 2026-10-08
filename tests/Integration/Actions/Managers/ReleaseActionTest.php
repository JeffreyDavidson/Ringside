<?php

declare(strict_types=1);

use App\Actions\Managers\ReleaseAction;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Roster\Wrestlers\WrestlerManager;

use function Pest\Laravel\assertDatabaseHas;

// The rules shared with wrestlers and referees are in tests/Integration/Actions/Individuals/ReleaseActionTest.php.

test('it ends current management relationships with the wrestlers and tag teams', function () {
    // Arrange
    $manager = Manager::factory()->employed()->create();
    $wrestler = Wrestler::factory()->employed()->create();
    $tagTeam = TagTeam::factory()->create();
    $manager->wrestlers()->attach($wrestler->id, ['hired_at' => now()->subDays(30)]);
    $manager->tagTeams()->attach($tagTeam->id, ['hired_at' => now()->subDays(20)]);

    // Act
    resolve(ReleaseAction::class)->handle($manager);

    // Assert
    expect($manager->refresh()->currentWrestlers)->toBeEmpty()
        ->and($manager->currentTagTeams)->toBeEmpty();

    assertDatabaseHas('wrestlers_managers', [
        'manager_id' => $manager->id,
        'wrestler_id' => $wrestler->id,
        'fired_at' => now()->toDateTimeString(),
    ]);

    assertDatabaseHas('tag_teams_managers', [
        'manager_id' => $manager->id,
        'tag_team_id' => $tagTeam->id,
        'fired_at' => now()->toDateTimeString(),
    ]);
});

test('it ends the current management relationship and preserves the historical one', function () {
    // Arrange
    $manager = Manager::factory()->employed()->create();
    $wrestler = Wrestler::factory()->employed()->create();
    $manager->wrestlers()->attach($wrestler->id, [
        'hired_at' => now()->subDays(30),
        'fired_at' => now()->subDays(20),
    ]);
    $manager->wrestlers()->attach($wrestler->id, ['hired_at' => now()->subDays(10)]);

    // Act
    resolve(ReleaseAction::class)->handle($manager);

    // Assert
    $currentRelationship = WrestlerManager::query()
        ->whereBelongsTo($manager)
        ->where('hired_at', now()->subDays(10))
        ->firstOrFail();

    expect($manager->refresh()->wrestlers()->count())->toBe(2)
        ->and($manager->currentWrestlers)->toBeEmpty()
        ->and(requiredDate($currentRelationship->fired_at)->toDateTimeString())->toBe(now()->toDateTimeString());
});
