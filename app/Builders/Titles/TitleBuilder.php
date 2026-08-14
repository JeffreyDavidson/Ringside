<?php

declare(strict_types=1);

namespace App\Builders\Titles;

use App\Builders\Concerns\HasRetirementScopes;
use App\Models\Titles\Title;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of Title
 *
 * @extends Builder<TModel>
 */
class TitleBuilder extends Builder
{
    use HasRetirementScopes;

    public function undebuted(): static
    {
        return $this->whereDoesntHave('activityPeriods');
    }

    public function active(): static
    {
        return $this->whereHas('currentActivityPeriod');
    }

    public function inactive(): static
    {
        return $this->whereHas('previousActivityPeriods')
            ->whereDoesntHave('currentActivityPeriod')
            ->whereDoesntHave('futureActivityPeriod');
    }

    public function withPendingDebut(): static
    {
        return $this->whereHas('futureActivityPeriod');
    }
}
