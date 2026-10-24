<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\Individuals;

use App\Builders\Roster\TagTeamMembershipBuilder;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Scopes\PromotionContextScope;
use Illuminate\Support\Collection;

class CurrentTagTeamsLock
{
    /**
     * Lock the tag teams the wrestler is currently a member of, one row at a time in ascending id order.
     *
     * A tag team cascade locks the team and then its wrestlers, so a wrestler operation that also touches the team
     * (to vacate its titles) takes the team locks before its own row lock. Must run inside the caller's transaction.
     *
     * @param  TagTeam|null  $retainedTagTeam  A tag team whose membership stays open and that the caller already holds.
     * @return Collection<int, TagTeam>
     */
    public function lock(Wrestler $wrestler, ?TagTeam $retainedTagTeam = null): Collection
    {
        $memberships = TagTeamWrestler::query()
            ->forWrestlerId($wrestler->id)
            ->current()
            ->when($retainedTagTeam, fn (TagTeamMembershipBuilder $query, TagTeam $tagTeam): TagTeamMembershipBuilder => $query->excludingTagTeamId($tagTeam->id))
            ->orderBy('tag_team_id')
            ->get();

        return $memberships
            ->map(fn (TagTeamWrestler $membership): ?TagTeam => TagTeam::query()
                ->withoutGlobalScope(PromotionContextScope::class)
                ->whereKey($membership->tag_team_id)
                ->lockForUpdate()
                ->first())
            ->filter()
            ->values();
    }
}
