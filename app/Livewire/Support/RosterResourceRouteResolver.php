<?php

declare(strict_types=1);

namespace App\Livewire\Support;

use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

final class RosterResourceRouteResolver
{
    public function urlFor(Wrestler|TagTeam $rosterMember): string
    {
        return route($this->routeName($rosterMember), $rosterMember);
    }

    private function routeName(Wrestler|TagTeam $rosterMember): string
    {
        return match (true) {
            $rosterMember instanceof Wrestler => 'wrestlers.show',
            $rosterMember instanceof TagTeam => 'tag-teams.show',
        };
    }
}
