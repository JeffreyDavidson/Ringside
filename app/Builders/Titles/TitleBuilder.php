<?php

declare(strict_types=1);

namespace App\Builders\Titles;

use App\Builders\Concerns\FiltersByName;
use App\Builders\Concerns\FiltersByRetirementStatus;
use App\Builders\Concerns\ProjectsActivityStatus;
use App\Models\Titles\Title;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of Title
 *
 * @extends Builder<TModel>
 */
class TitleBuilder extends Builder
{
    use FiltersByName;
    use FiltersByRetirementStatus;
    use ProjectsActivityStatus;

    public function undebuted(): static
    {
        return $this->whereDoesntHave('activityPeriods')
            ->whereDoesntHave('currentRetirement');
    }

    public function active(): static
    {
        return $this->whereHas('currentActivityPeriod')
            ->whereDoesntHave('currentRetirement');
    }

    public function inactive(): static
    {
        return $this->whereHas('previousActivityPeriods')
            ->whereDoesntHave('currentActivityPeriod')
            ->whereDoesntHave('futureActivityPeriod')
            ->whereDoesntHave('currentRetirement');
    }

    public function withPendingDebut(): static
    {
        return $this->whereHas('futureActivityPeriod')
            ->whereDoesntHave('currentRetirement');
    }
}
