<?php

declare(strict_types=1);

use App\Models\TagTeams\TagTeam;
use App\Models\Titles\TitleChampionship;
use App\Models\Wrestlers\Wrestler;

it('filters current and previous championships', function () {
    $currentChampionship = TitleChampionship::factory()->current()->create();
    $previousChampionship = TitleChampionship::factory()->ended()->create();

    $currentChampionships = TitleChampionship::query()->current()->get();
    $previousChampionships = TitleChampionship::query()->previous()->get();

    expect($currentChampionships)->toHaveCount(1)
        ->and($currentChampionships->firstOrFail()->is($currentChampionship))->toBeTrue()
        ->and($previousChampionships)->toHaveCount(1)
        ->and($previousChampionships->firstOrFail()->is($previousChampionship))->toBeTrue();
});

it('filters championships by supported champion type', function () {
    $wrestler = Wrestler::factory()->create();
    $tagTeam = TagTeam::factory()->create();
    $wrestlerChampionship = TitleChampionship::factory()->forWrestler($wrestler)->create();
    $tagTeamChampionship = TitleChampionship::factory()->forTagTeam($tagTeam)->create();

    $wrestlerChampionships = TitleChampionship::query()->forChampion($wrestler)->get();
    $tagTeamChampionships = TitleChampionship::query()->forChampion($tagTeam)->get();

    expect($wrestlerChampionships)->toHaveCount(1)
        ->and($wrestlerChampionships->firstOrFail()->is($wrestlerChampionship))->toBeTrue()
        ->and($tagTeamChampionships)->toHaveCount(1)
        ->and($tagTeamChampionships->firstOrFail()->is($tagTeamChampionship))->toBeTrue();
});
