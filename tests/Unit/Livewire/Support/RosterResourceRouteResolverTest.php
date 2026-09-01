<?php

declare(strict_types=1);

use App\Livewire\Support\RosterResourceRouteResolver;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

it('resolves a wrestler resource URL', function () {
    $wrestler = Wrestler::factory()->make(['id' => 1]);

    expect(app(RosterResourceRouteResolver::class)->urlFor($wrestler))
        ->toBe(route('wrestlers.show', $wrestler));
});

it('resolves a tag team resource URL', function () {
    $tagTeam = TagTeam::factory()->make(['id' => 1]);

    expect(app(RosterResourceRouteResolver::class)->urlFor($tagTeam))
        ->toBe(route('tag-teams.show', $tagTeam));
});
