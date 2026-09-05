<?php

declare(strict_types=1);

use App\Livewire\Matches\Support\MatchCompetitorStateMapper;
use App\Models\Matches\EventMatch;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

describe('match competitor form state', function (): void {
    it('maps each match side to its wrestler and tag team identifiers', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()->create();
        $tagTeam = TagTeam::factory()->create();
        $match = EventMatch::factory()
            ->withCompetitors([$wrestler, $tagTeam])
            ->create();
        $sides = $match->sides()
            ->with('competitors.competitor')
            ->get();
        $mapper = new MatchCompetitorStateMapper();

        // Act
        $competitors = $mapper->fromSides($sides, false);

        // Assert
        expect($competitors)->toBe([
            ['wrestlers' => [$wrestler->id], 'tag_teams' => []],
            ['wrestlers' => [], 'tag_teams' => [$tagTeam->id]],
        ]);
    });

    it('flattens individual sides into one wrestler selection list', function (): void {
        // Arrange
        $wrestlers = Wrestler::factory()->count(2)->create();
        $match = EventMatch::factory()
            ->withCompetitors($wrestlers->all())
            ->create();
        $sides = $match->sides()
            ->with('competitors.competitor')
            ->get();
        $mapper = new MatchCompetitorStateMapper();

        // Act
        $competitors = $mapper->fromSides($sides, true);

        // Assert
        expect($competitors)->toBe([
            [
                'wrestlers' => $wrestlers->pluck('id')->all(),
                'tag_teams' => [],
            ],
        ]);
    });
});
