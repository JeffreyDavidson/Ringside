<?php

declare(strict_types=1);

use App\Enums\MatchFinish;
use App\Livewire\Matches\Support\MatchCompetitorRouteResolver;
use App\Livewire\Matches\Support\MatchTableFormatter;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Referees\Referee;
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

    it('marks only the competitors passed as unbookable', function (): void {
        // Arrange
        $unbookable = Wrestler::factory()->create(['name' => 'Unbookable']);
        $healthy = Wrestler::factory()->create(['name' => 'Healthy']);
        $match = EventMatch::factory()
            ->withCompetitors([$unbookable, $healthy])
            ->create();
        $match->load(['competitors.side', 'competitors.competitor']);
        $formatter = app(MatchTableFormatter::class);

        // Act
        $competitorLinks = $formatter->competitorLinks($match, [MatchTableFormatter::unbookableKey($unbookable) => true]);

        // Assert
        expect($competitorLinks)
            ->toBe('<a href="'.route('wrestlers.show', $unbookable).'">Unbookable</a>'.MatchCompetitorRouteResolver::unbookableMarker().' vs <a href="'.route('wrestlers.show', $healthy).'">Healthy</a>');
    });

    it('formats referees as escaped links with an optional unbookable marker', function (): void {
        // Arrange
        $referee = Referee::factory()->create(['first_name' => '<Ref>', 'last_name' => 'Smith'])->refresh();
        $formatter = app(MatchTableFormatter::class);

        // Act
        $plain = $formatter->refereeLink($referee);
        $marked = $formatter->refereeLink($referee, true);

        // Assert
        $link = '<a href="'.route('referees.show', $referee).'">&lt;Ref&gt; Smith</a>';
        expect($plain)->toBe($link)
            ->and($marked)->toBe($link.MatchCompetitorRouteResolver::unbookableMarker());
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

    it('formats a decisive result with the winning side and finish', function (): void {
        // Arrange
        $winner = Wrestler::factory()->create(['name' => 'Winning Wrestler']);
        $loser = Wrestler::factory()->create(['name' => 'Losing Wrestler']);
        $match = EventMatch::factory()
            ->withCompetitors([$winner, $loser])
            ->create();
        $match->update([
            'match_finish' => MatchFinish::Pinfall,
            'winning_side_id' => $match->sides()->whereRelation('competitors', 'competitor_id', $winner->id)->firstOrFail()->id,
        ]);
        $formatter = app(MatchTableFormatter::class);

        // Act
        $result = $formatter->result($match->fresh());

        // Assert
        expect($result)->toBe('<a href="'.route('wrestlers.show', $winner).'">Winning Wrestler</a> by '.MatchFinish::Pinfall->label());
    });

    it('formats a finish without a winning side as the finish label', function (): void {
        // Arrange
        $match = EventMatch::factory()->create([
            'match_finish' => MatchFinish::TimeLimitDraw,
            'winning_side_id' => null,
        ]);
        $formatter = app(MatchTableFormatter::class);

        // Act
        $result = $formatter->result($match);

        // Assert
        expect($result)->toBe(MatchFinish::TimeLimitDraw->label());
    });
});
