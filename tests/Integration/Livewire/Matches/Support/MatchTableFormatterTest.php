<?php

declare(strict_types=1);

use App\Livewire\Matches\Support\MatchTableFormatter;
use App\Models\Matches\EventMatch;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

it('formats competitors by side as resource links', function () {
    $wrestler = Wrestler::factory()->create(['name' => '<Wrestler>']);
    $tagTeam = TagTeam::factory()->create(['name' => 'The Tag Team']);
    $match = EventMatch::factory()
        ->withCompetitors([$wrestler, $tagTeam])
        ->create();

    $match->load(['competitors.side', 'competitors.competitor']);

    expect(app(MatchTableFormatter::class)->competitorLinks($match))
        ->toBe('<a href="'.route('wrestlers.show', $wrestler).'">&lt;Wrestler&gt;</a> vs <a href="'.route('tag-teams.show', $tagTeam).'">The Tag Team</a>');
});

it('formats an unfinished match result as unavailable', function () {
    $match = EventMatch::factory()->create();

    expect(app(MatchTableFormatter::class)->result($match))->toBe('N/A');
});
