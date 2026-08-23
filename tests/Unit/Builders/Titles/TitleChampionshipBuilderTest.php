<?php

declare(strict_types=1);

use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;

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

it('filters championships by wrestler and tag team ids', function () {
    $wrestler = Wrestler::factory()->create();
    $tagTeam = TagTeam::factory()->create();
    $wrestlerChampionship = TitleChampionship::factory()->forWrestler($wrestler)->create();
    $tagTeamChampionship = TitleChampionship::factory()->forTagTeam($tagTeam)->create();

    expect(TitleChampionship::query()->forWrestlerId($wrestler->id)->pluck('id')->all())
        ->toBe([$wrestlerChampionship->id])
        ->and(TitleChampionship::query()->forTagTeamId($tagTeam->id)->pluck('id')->all())
        ->toBe([$tagTeamChampionship->id]);
});

it('filters and orders title championship history', function () {
    $title = Title::factory()->create();
    $otherTitle = Title::factory()->create();
    $firstChampionship = TitleChampionship::factory()->for($title)->ended()->create([
        'won_at' => now()->subYears(4),
        'lost_at' => now()->subYears(3),
    ]);
    $latestChampionship = TitleChampionship::factory()->for($title)->ended()->create([
        'won_at' => now()->subYears(2),
        'lost_at' => now()->subYear(),
    ]);
    TitleChampionship::factory()->for($otherTitle)->ended()->create([
        'won_at' => now()->subMonths(2),
        'lost_at' => now()->subMonth(),
    ]);

    $championshipsByWinDate = TitleChampionship::query()
        ->forTitleId($title->id)
        ->earliestWonFirst()
        ->get();
    $championshipsByLossDate = TitleChampionship::query()
        ->forTitleId($title->id)
        ->previous()
        ->mostRecentlyLostFirst()
        ->get();

    expect($championshipsByWinDate->modelKeys())->toBe([
        $firstChampionship->id,
        $latestChampionship->id,
    ])->and($championshipsByLossDate->modelKeys())->toBe([
        $latestChampionship->id,
        $firstChampionship->id,
    ]);
});

it('builds previous championship history with display relationships', function () {
    $title = Title::factory()->create();
    $champion = Wrestler::factory()->create();
    $firstChampionship = TitleChampionship::factory()
        ->for($title)
        ->forWrestler($champion)
        ->ended()
        ->create([
            'won_at' => now()->subYears(2),
            'lost_at' => now()->subYear()->subDay(),
        ]);
    $latestChampionship = TitleChampionship::factory()
        ->for($title)
        ->forWrestler($champion)
        ->ended()
        ->create([
            'won_at' => now()->subYear(),
            'lost_at' => now()->subMonth(),
        ]);

    $history = TitleChampionship::query()
        ->forChampion($champion)
        ->forPreviousHistory()
        ->get();

    expect($history->modelKeys())->toBe([$latestChampionship->id, $firstChampionship->id])
        ->and($history->firstOrFail()->getAttribute('previous_championship_id'))->toBe($firstChampionship->id)
        ->and($history->firstOrFail()->relationLoaded('title'))->toBeTrue()
        ->and($history->firstOrFail()->relationLoaded('previousChampionship'))->toBeTrue()
        ->and($history->firstOrFail()->previousChampionship?->champion?->is($champion))->toBeTrue();
});
