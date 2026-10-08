<?php

declare(strict_types=1);

use App\Actions\Wrestlers\RetireAction;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;

// The rules shared with managers and referees are in tests/Integration/Actions/Individuals/RetireActionTest.php.

test('it ends the current tag team, stable, manager and championship when the wrestler retires', function () {
    // Arrange
    $wrestler = Wrestler::factory()->employed()->create();
    $tagTeam = TagTeam::factory()->create();
    $stable = Stable::factory()->create();
    $manager = Manager::factory()->create();
    $wrestler->tagTeams()->attach($tagTeam, ['joined_at' => now()->subDay()]);
    $wrestler->stables()->attach($stable, ['joined_at' => now()->subDay()]);
    $wrestler->managers()->attach($manager, ['hired_at' => now()->subDay()]);
    $championship = TitleChampionship::factory()
        ->for(Title::factory()->create(), 'title')
        ->for($wrestler, 'champion')
        ->current()
        ->create();

    // Act
    resolve(RetireAction::class)->handle($wrestler);

    // Assert
    expect($wrestler->refresh()->currentTagTeam)->toBeNull()
        ->and($wrestler->currentStable)->toBeNull()
        ->and($wrestler->currentManagers)->toBeEmpty()
        ->and($championship->refresh()->lost_at?->toDateTimeString())->toBe(now()->toDateTimeString());
});
