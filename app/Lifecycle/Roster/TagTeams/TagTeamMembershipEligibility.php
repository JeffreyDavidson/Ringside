<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\TagTeams;

use App\Exceptions\Roster\TagTeams\CannotBeEstablishedException;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;

final class TagTeamMembershipEligibility
{
    /**
     * Reject wrestlers who currently belong to a different tag team, including a soft-deleted one.
     *
     * Callers must hold the wrestlers' row locks so the check cannot race another membership write.
     *
     * @param  Collection<int, Wrestler>  $wrestlers
     */
    public function ensureWrestlersCanJoin(TagTeam $tagTeam, Collection $wrestlers): void
    {
        $membership = TagTeamWrestler::query()
            ->current()
            ->whereIn('wrestler_id', $wrestlers->modelKeys())
            ->where('tag_team_id', '!=', $tagTeam->getKey())
            ->orderBy('wrestler_id')
            ->first();

        if ($membership === null) {
            return;
        }

        throw CannotBeEstablishedException::wrestlerOnAnotherTagTeam(
            Wrestler::query()->findOrFail($membership->wrestler_id),
            TagTeam::query()->withTrashed()->findOrFail($membership->tag_team_id),
        );
    }
}
