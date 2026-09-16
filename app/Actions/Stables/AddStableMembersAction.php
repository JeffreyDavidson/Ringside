<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Data\Stables\StableMembershipData;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\Stables\StableTagTeam;
use App\Models\Roster\Stables\StableWrestler;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AddStableMembersAction
{
    public function handle(Stable $stable, StableMembershipData $members, Carbon $joinedAt): void
    {
        $this->attachMembers($stable->wrestlers(), $members->wrestlers, $joinedAt);
        $this->attachMembers($stable->tagTeams(), $members->tagTeams, $joinedAt);
    }

    /**
     * @template TRelatedModel of Wrestler|TagTeam
     * @template TPivotModel of StableWrestler|StableTagTeam
     *
     * @param  BelongsToMany<TRelatedModel, Stable, TPivotModel>  $relationship
     * @param  Collection<int, TRelatedModel>|null  $members
     */
    private function attachMembers(
        BelongsToMany $relationship,
        ?Collection $members,
        Carbon $joinedAt,
    ): void {
        if ($members === null || $members->isEmpty()) {
            return;
        }

        $relationship->attach($members->map(
            fn (Wrestler|TagTeam $member): int => Arr::integer(['key' => $member->getKey()], 'key'),
        )->all(), [
            'joined_at' => $joinedAt,
            'left_at' => null,
        ]);
    }
}
