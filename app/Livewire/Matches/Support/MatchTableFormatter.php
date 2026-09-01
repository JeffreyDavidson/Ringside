<?php

declare(strict_types=1);

namespace App\Livewire\Matches\Support;

use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Collection;

final class MatchTableFormatter
{
    public function __construct(private MatchCompetitorRouteResolver $routeResolver) {}

    public function competitorLinks(EventMatch $match): string
    {
        return $match->competitors
            ->competitorModelsBySidePosition()
            ->map(fn (Collection $side): string => $side
                ->map(fn (Wrestler|TagTeam $competitor): string => $this->routeResolver->link($competitor))
                ->join(' & '))
            ->join(' vs ');
    }

    public function result(EventMatch $match): string
    {
        if ($match->match_finish === null) {
            return 'N/A';
        }

        if ($match->winningSide !== null) {
            $winners = $match->winningSide->competitors
                ->map(fn (MatchCompetitor $competitor): string => $this->routeResolver->link($competitor->competitor))
                ->join(' & ');

            return $winners.' by '.$match->match_finish->label();
        }

        return $match->match_finish->label();
    }
}
