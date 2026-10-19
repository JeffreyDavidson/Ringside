<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Scopes\PromotionContextScope;
use Illuminate\Support\Collection;

class LockCurrentTagTeamsAction
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
    public function handle(Wrestler $wrestler, ?TagTeam $retainedTagTeam = null): Collection
    {
        $tagTeamIds = TagTeamWrestler::query()
            ->forWrestlerId($wrestler->id)
            ->current()
            ->when($retainedTagTeam, fn ($query, TagTeam $tagTeam) => $query->excludingTagTeamId($tagTeam->id))
            ->orderBy('tag_team_id')
            ->pluck('tag_team_id');

        return $tagTeamIds
            ->map(fn (int $tagTeamId): ?TagTeam => TagTeam::query()
                ->withoutGlobalScope(PromotionContextScope::class)
                ->whereKey($tagTeamId)
                ->lockForUpdate()
                ->first())
            ->filter()
            ->values();
    }
}
