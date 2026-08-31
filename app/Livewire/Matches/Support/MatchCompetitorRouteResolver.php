<?php

declare(strict_types=1);

namespace App\Livewire\Matches\Support;

use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

final class MatchCompetitorRouteResolver
{
    public function link(Wrestler|TagTeam $competitor): string
    {
        return '<a href="'.e(route($this->routeName($competitor), $competitor)).'">'.e($competitor->name).'</a>';
    }

    private function routeName(Wrestler|TagTeam $competitor): string
    {
        return match (true) {
            $competitor instanceof Wrestler => 'wrestlers.show',
            $competitor instanceof TagTeam => 'tag-teams.show',
        };
    }
}
