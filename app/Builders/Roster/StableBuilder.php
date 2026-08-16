<?php

declare(strict_types=1);

namespace App\Builders\Roster;

use App\Builders\Concerns\ProjectsActivityStatus;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\Stables\StableTagTeam;
use App\Models\Roster\Stables\StableWrestler;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of Stable
 *
 * @extends Builder<TModel>
 */
class StableBuilder extends Builder
{
    use ProjectsActivityStatus;

    public function previousForTagTeamId(int $tagTeamId): static
    {
        $membership = new StableTagTeam();

        $this->join($membership->getTable(), 'stables.id', '=', $membership->qualifyColumn('stable_id'))
            ->where($membership->qualifyColumn('tag_team_id'), $tagTeamId)
            ->whereNotNull($membership->qualifyColumn('left_at'))
            ->select(
                'stables.*',
                $membership->qualifyColumn('joined_at').' as joined_at',
                $membership->qualifyColumn('left_at').' as left_at',
            )
            ->orderByDesc($membership->qualifyColumn('joined_at'));

        return $this;
    }

    public function previousForWrestlerId(int $wrestlerId): static
    {
        $membership = new StableWrestler();

        $this->join($membership->getTable(), 'stables.id', '=', $membership->qualifyColumn('stable_id'))
            ->where($membership->qualifyColumn('wrestler_id'), $wrestlerId)
            ->whereNotNull($membership->qualifyColumn('left_at'))
            ->select(
                'stables.*',
                $membership->qualifyColumn('joined_at').' as joined_at',
                $membership->qualifyColumn('left_at').' as left_at',
            )
            ->orderByDesc($membership->qualifyColumn('joined_at'));

        return $this;
    }

    public function unestablished(): static
    {
        return $this->whereDoesntHave('activityPeriods');
    }

    public function established(): static
    {
        return $this->whereHas('currentActivityPeriod');
    }

    public function disbanded(): static
    {
        return $this->whereHas('previousActivityPeriods')
            ->whereDoesntHave('currentActivityPeriod')
            ->whereDoesntHave('currentRetirement');
    }

    public function withFutureEstablishment(): static
    {
        return $this->whereHas('futureActivityPeriod');
    }
}
