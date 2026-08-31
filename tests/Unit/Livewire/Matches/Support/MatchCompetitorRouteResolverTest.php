<?php

declare(strict_types=1);

use App\Livewire\Matches\Support\MatchCompetitorRouteResolver;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

it('links wrestlers to their resource route', function () {
    $wrestler = Wrestler::factory()->make(['id' => 1]);

    expect(app(MatchCompetitorRouteResolver::class)->link($wrestler))
        ->toBe('<a href="'.route('wrestlers.show', $wrestler).'">'.$wrestler->name.'</a>');
});

it('links tag teams to their resource route', function () {
    $tagTeam = TagTeam::factory()->make(['id' => 1]);

    expect(app(MatchCompetitorRouteResolver::class)->link($tagTeam))
        ->toBe('<a href="'.route('tag-teams.show', $tagTeam).'">'.$tagTeam->name.'</a>');
});

it('escapes competitor names in generated links', function () {
    $wrestler = Wrestler::factory()->make(['id' => 1, 'name' => '<script>alert(1)</script>']);

    expect(app(MatchCompetitorRouteResolver::class)->link($wrestler))
        ->toBe('<a href="'.route('wrestlers.show', $wrestler).'">&lt;script&gt;alert(1)&lt;/script&gt;</a>');
});
