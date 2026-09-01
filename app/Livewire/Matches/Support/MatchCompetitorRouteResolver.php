<?php

declare(strict_types=1);

namespace App\Livewire\Matches\Support;

use App\Livewire\Support\RosterResourceRouteResolver;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

final class MatchCompetitorRouteResolver
{
    public function __construct(private readonly RosterResourceRouteResolver $routeResolver) {}

    public function link(Wrestler|TagTeam $competitor): string
    {
        return '<a href="'.e($this->routeResolver->urlFor($competitor)).'">'.e($competitor->name).'</a>';
    }
}
