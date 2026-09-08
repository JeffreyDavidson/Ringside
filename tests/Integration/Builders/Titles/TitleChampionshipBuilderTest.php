<?php

declare(strict_types=1);

use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;

it('filters current and previous championships', function () {
    // Arrange
    $currentChampionship = TitleChampionship::factory()->current()->create();
    $previousChampionship = TitleChampionship::factory()->ended()->create();
    TitleChampionship::factory()->current()->trashed()->create();
    TitleChampionship::factory()->ended()->trashed()->create();

    // Act
    $currentQuery = TitleChampionship::query();
    $currentQuery->current();
    $currentChampionships = $currentQuery->get();
    $previousQuery = TitleChampionship::query();
    $previousQuery->previous();
    $previousChampionships = $previousQuery->get();

    // Assert
    expect($currentChampionships->modelKeys())->toBe([$currentChampionship->id])
        ->and($previousChampionships->modelKeys())->toBe([$previousChampionship->id]);
});

it('filters championships by supported champion type', function () {
    // Arrange
    $wrestler = Wrestler::factory()->create();
    $tagTeam = TagTeam::factory()->create(['id' => $wrestler->id]);
    $wrestlerChampionship = TitleChampionship::factory()->for(Title::factory()->singles())->forWrestler($wrestler)->create();
    $tagTeamChampionship = TitleChampionship::factory()->for(Title::factory()->tagTeam())->forTagTeam($tagTeam)->create();
    TitleChampionship::factory()->for(Title::factory()->singles())->forWrestler(Wrestler::factory()->create())->create();
    TitleChampionship::factory()->for(Title::factory()->tagTeam())->forTagTeam(TagTeam::factory()->create())->create();
    TitleChampionship::factory()->for(Title::factory()->singles())->forWrestler($wrestler)->trashed()->create();
    TitleChampionship::factory()->for(Title::factory()->tagTeam())->forTagTeam($tagTeam)->trashed()->create();

    // Act
    $wrestlerQuery = TitleChampionship::query();
    $wrestlerQuery->forChampion($wrestler);
    $wrestlerChampionships = $wrestlerQuery->get();
    $tagTeamQuery = TitleChampionship::query();
    $tagTeamQuery->forChampion($tagTeam);
    $tagTeamChampionships = $tagTeamQuery->get();

    // Assert
    expect($wrestlerChampionships->modelKeys())->toBe([$wrestlerChampionship->id])
        ->and($tagTeamChampionships->modelKeys())->toBe([$tagTeamChampionship->id]);
});

it('filters championships by wrestler and tag team ids', function () {
    // Arrange
    $wrestler = Wrestler::factory()->create();
    $tagTeam = TagTeam::factory()->create(['id' => $wrestler->id]);
    $wrestlerChampionship = TitleChampionship::factory()->for(Title::factory()->singles())->forWrestler($wrestler)->create();
    $tagTeamChampionship = TitleChampionship::factory()->for(Title::factory()->tagTeam())->forTagTeam($tagTeam)->create();
    TitleChampionship::factory()->for(Title::factory()->singles())->forWrestler(Wrestler::factory()->create())->create();
    TitleChampionship::factory()->for(Title::factory()->tagTeam())->forTagTeam(TagTeam::factory()->create())->create();
    TitleChampionship::factory()->for(Title::factory()->singles())->forWrestler($wrestler)->trashed()->create();
    TitleChampionship::factory()->for(Title::factory()->tagTeam())->forTagTeam($tagTeam)->trashed()->create();

    // Act
    $wrestlerQuery = TitleChampionship::query();
    $wrestlerQuery->forWrestlerId($wrestler->id);
    $wrestlerChampionships = $wrestlerQuery->get();
    $tagTeamQuery = TitleChampionship::query();
    $tagTeamQuery->forTagTeamId($tagTeam->id);
    $tagTeamChampionships = $tagTeamQuery->get();

    // Assert
    expect($wrestlerChampionships->modelKeys())->toBe([$wrestlerChampionship->id])
        ->and($tagTeamChampionships->modelKeys())->toBe([$tagTeamChampionship->id]);
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
    // Arrange
    $title = Title::factory()->singles()->create();
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

    TitleChampionship::factory()
        ->for($title)
        ->forWrestler($champion)
        ->current()
        ->create(['won_at' => now()->subWeek()]);
    TitleChampionship::factory()
        ->for(Title::factory()->singles())
        ->forWrestler(Wrestler::factory()->create())
        ->ended()
        ->create([
            'won_at' => now()->subMonth(),
            'lost_at' => now()->subDay(),
        ]);

    // Act
    $query = TitleChampionship::query();
    $query->forChampion($champion);
    $query->forPreviousHistory();
    $history = $query->get();

    // Assert
    expect($history->modelKeys())->toBe([$latestChampionship->id, $firstChampionship->id])
        ->and($history->firstOrFail()->previous_championship_id)->toBe($firstChampionship->id)
        ->and($history->last()?->previous_championship_id)->toBeNull()
        ->and($history->firstOrFail()->relationLoaded('title'))->toBeTrue()
        ->and($history->firstOrFail()->title?->is($title))->toBeTrue()
        ->and($history->firstOrFail()->relationLoaded('previousChampionship'))->toBeTrue()
        ->and($history->firstOrFail()->previousChampionship?->is($firstChampionship))->toBeTrue()
        ->and($history->firstOrFail()->previousChampionship?->champion?->is($champion))->toBeTrue();
});
