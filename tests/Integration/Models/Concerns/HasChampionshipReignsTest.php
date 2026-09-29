<?php

declare(strict_types=1);

use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use Illuminate\Database\Eloquent\Relations\MorphMany;

it('lists only ended reigns as previous title championships', function (string $championClass, string $forChampion) {
    $champion = $championClass::factory()->create();
    $otherChampion = $championClass::factory()->create();
    $endedReign = TitleChampionship::factory()->for(Title::factory())->{$forChampion}($champion)->ended()->create();
    TitleChampionship::factory()->for(Title::factory())->{$forChampion}($champion)->current()->create();
    TitleChampionship::factory()->for(Title::factory())->{$forChampion}($otherChampion)->ended()->create();

    $relation = $champion->previousTitleChampionships();
    $previousChampionships = $relation->get();

    expect($relation)->toBeInstanceOf(MorphMany::class)
        ->and($previousChampionships)->toHaveCount(1)
        ->and($previousChampionships->first()?->is($endedReign))->toBeTrue();
})->with([
    'wrestler' => [Wrestler::class, 'forWrestler'],
    'tag team' => [TagTeam::class, 'forTagTeam'],
]);

it('lists only open reigns as current title championships', function (string $championClass, string $forChampion) {
    $champion = $championClass::factory()->create();
    TitleChampionship::factory()->for(Title::factory())->{$forChampion}($champion)->ended()->create();
    $currentReign = TitleChampionship::factory()->for(Title::factory())->{$forChampion}($champion)->current()->create();

    $currentChampionships = $champion->currentChampionships()->get();

    expect($currentChampionships)->toHaveCount(1)
        ->and($currentChampionships->first()?->is($currentReign))->toBeTrue();
})->with([
    'wrestler' => [Wrestler::class, 'forWrestler'],
    'tag team' => [TagTeam::class, 'forTagTeam'],
]);
