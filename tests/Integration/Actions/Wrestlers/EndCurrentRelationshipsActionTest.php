<?php

declare(strict_types=1);

use App\Actions\Wrestlers\EndCurrentRelationshipsAction;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;

test('it ends the wrestlers current professional relationships', function () {
    $wrestler = Wrestler::factory()->create();
    $tagTeam = TagTeam::factory()->create();
    $stable = Stable::factory()->create();
    $manager = Manager::factory()->create();
    $title = Title::factory()->create();
    $effectiveDate = now();

    $wrestler->tagTeams()->attach($tagTeam, ['joined_at' => now()->subDay()]);
    $wrestler->stables()->attach($stable, ['joined_at' => now()->subDay()]);
    $wrestler->managers()->attach($manager, ['hired_at' => now()->subDay()]);
    $championship = TitleChampionship::factory()
        ->for($title, 'title')
        ->for($wrestler, 'champion')
        ->current()
        ->create();

    resolve(EndCurrentRelationshipsAction::class)
        ->handle($wrestler, $effectiveDate);

    $wrestler->refresh();
    $championship->refresh();

    expect($wrestler->currentTagTeam)->toBeNull()
        ->and($wrestler->currentStable)->toBeNull()
        ->and($wrestler->currentManagers)->toBeEmpty()
        ->and($championship->lost_at?->toDateTimeString())->toBe($effectiveDate->toDateTimeString());
});
