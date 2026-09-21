<?php

declare(strict_types=1);

use App\Livewire\Matches\Support\MatchCompetitorRouteResolver;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

describe('match competitor resource links', function (): void {
    it('links wrestlers to their resource route', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()->make(['id' => 1]);
        $resolver = app(MatchCompetitorRouteResolver::class);

        // Act
        $link = $resolver->link($wrestler);

        // Assert
        expect($link)
            ->toBe('<a href="'.route('wrestlers.show', $wrestler).'">'.e($wrestler->name).'</a>');
    });

    it('links tag teams to their resource route', function (): void {
        // Arrange
        $tagTeam = TagTeam::factory()->make(['id' => 1]);
        $resolver = app(MatchCompetitorRouteResolver::class);

        // Act
        $link = $resolver->link($tagTeam);

        // Assert
        expect($link)
            ->toBe('<a href="'.route('tag-teams.show', $tagTeam).'">'.e($tagTeam->name).'</a>');
    });

    it('escapes competitor names in generated links', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()->make(['id' => 1, 'name' => '<script>alert(1)</script>']);
        $resolver = app(MatchCompetitorRouteResolver::class);

        // Act
        $link = $resolver->link($wrestler);

        // Assert
        expect($link)
            ->toBe('<a href="'.route('wrestlers.show', $wrestler).'">&lt;script&gt;alert(1)&lt;/script&gt;</a>');
    });
});
