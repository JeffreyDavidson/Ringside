<?php

declare(strict_types=1);

namespace App\Builders\Roster;

use App\Builders\Concerns\ProjectsActivityStatus;
use App\Models\Stables\Stable;
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
        $this->join('stables_tag_teams', 'stables.id', '=', 'stables_tag_teams.stable_id')
            ->where('stables_tag_teams.tag_team_id', $tagTeamId)
            ->whereNotNull('stables_tag_teams.left_at')
            ->select(
                'stables.*',
                'stables_tag_teams.joined_at as joined_at',
                'stables_tag_teams.left_at as left_at',
            )
            ->orderByDesc('stables_tag_teams.joined_at');

        return $this;
    }

    public function previousForWrestlerId(int $wrestlerId): static
    {
        $this->join('stables_wrestlers', 'stables.id', '=', 'stables_wrestlers.stable_id')
            ->where('stables_wrestlers.wrestler_id', $wrestlerId)
            ->whereNotNull('stables_wrestlers.left_at')
            ->select(
                'stables.*',
                'stables_wrestlers.joined_at as joined_at',
                'stables_wrestlers.left_at as left_at',
            )
            ->orderByDesc('stables_wrestlers.joined_at');

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
