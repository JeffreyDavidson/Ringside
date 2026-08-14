<?php

declare(strict_types=1);

namespace App\Builders\Roster;

use App\Builders\Concerns\HasRetirementScopes;
use App\Models\Stables\Stable;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of Stable
 *
 * @extends Builder<TModel>
 */
class StableBuilder extends Builder
{
    use HasRetirementScopes;

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
