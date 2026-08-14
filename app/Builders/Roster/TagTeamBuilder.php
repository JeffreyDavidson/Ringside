<?php

declare(strict_types=1);

namespace App\Builders\Roster;

use App\Builders\Concerns\HasEmploymentScopes;
use App\Builders\Concerns\HasRetirementScopes;
use App\Models\TagTeams\TagTeam;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of TagTeam
 *
 * @extends Builder<TModel>
 */
class TagTeamBuilder extends Builder
{
    use HasEmploymentScopes;
    use HasRetirementScopes;

    public function available(): static
    {
        return $this->whereHas('currentEmployment')
            ->whereDoesntHave('currentSuspension')
            ->whereDoesntHave('currentRetirement');
    }

    public function unavailable(): static
    {
        return $this->where(function (Builder $query): void {
            $query->whereDoesntHave('currentEmployment')
                ->orWhereHas('currentSuspension')
                ->orWhereHas('currentRetirement');
        });
    }

    public function suspended(): static
    {
        return $this->whereHas('currentSuspension');
    }

    public function withMinimumWrestlers(int $count = 2): static
    {
        return $this->has('currentWrestlers', '>=', $count);
    }

    public function belowMinimumWrestlers(int $count = 2): static
    {
        return $this->has('currentWrestlers', '<', $count);
    }
}
