<?php

declare(strict_types=1);

namespace App\Builders\Roster;

use App\Models\TagTeams\TagTeamWrestler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * @template TModel of TagTeamWrestler
 *
 * @extends MembershipPeriodBuilder<TModel>
 */
class TagTeamMembershipBuilder extends MembershipPeriodBuilder
{
    public function forTagTeamId(int $tagTeamId): static
    {
        $this->where('tag_team_id', $tagTeamId);

        return $this;
    }

    public function forWrestlerId(int $wrestlerId): static
    {
        $this->where('wrestler_id', $wrestlerId);

        return $this;
    }

    public function excludingWrestlerId(int $wrestlerId): static
    {
        $this->where('wrestler_id', '!=', $wrestlerId);

        return $this;
    }

    public function overlappingPeriod(Carbon $periodStart, Carbon $periodEnd): static
    {
        $this->where('joined_at', '<=', $periodEnd)
            ->where(function (Builder $query) use ($periodStart): void {
                $query->whereNull('left_at')
                    ->orWhere('left_at', '>=', $periodStart);
            });

        return $this;
    }
}
