<?php

declare(strict_types=1);

use App\Livewire\Matches\Support\MatchTableFormatter;
use App\Models\Matches\EventMatch;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

describe('match table formatting', function (): void {
    it('formats competitors by side as escaped resource links', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()->create(['name' => '<Wrestler>']);
        $tagTeam = TagTeam::factory()->create(['name' => 'The Tag Team']);
        $match = EventMatch::factory()
            ->withCompetitors([$wrestler, $tagTeam])
            ->create();
        $match->load(['competitors.side', 'competitors.competitor']);
        $formatter = app(MatchTableFormatter::class);

        // Act
        $competitorLinks = $formatter->competitorLinks($match);

        // Assert
        expect($competitorLinks)
            ->toBe('<a href="'.route('wrestlers.show', $wrestler).'">&lt;Wrestler&gt;</a> vs <a href="'.route('tag-teams.show', $tagTeam).'">The Tag Team</a>');
    });

    it('formats an unfinished match result as unavailable', function (): void {
        // Arrange
        $match = EventMatch::factory()->create();
        $formatter = app(MatchTableFormatter::class);

        // Act
        $result = $formatter->result($match);

        // Assert
        expect($result)->toBe('N/A');
    });
});
