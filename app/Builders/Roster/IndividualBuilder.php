<?php

declare(strict_types=1);

namespace App\Builders\Roster;

use App\Builders\Concerns\HasEmploymentScopes;
use App\Builders\Concerns\HasRetirementScopes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 *
 * @extends Builder<TModel>
 */
abstract class IndividualBuilder extends Builder
{
    use HasEmploymentScopes;
    use HasRetirementScopes;

    public function available(): static
    {
        return $this->whereHas('currentEmployment')
            ->whereDoesntHave('currentInjury')
            ->whereDoesntHave('currentSuspension')
            ->whereDoesntHave('currentRetirement');
    }

    public function unavailable(): static
    {
        return $this->where(function (Builder $query): void {
            $query->whereDoesntHave('currentEmployment')
                ->orWhereHas('currentInjury')
                ->orWhereHas('currentSuspension')
                ->orWhereHas('currentRetirement');
        });
    }

    public function injured(): static
    {
        return $this->whereHas('currentInjury');
    }

    public function suspended(): static
    {
        return $this->whereHas('currentSuspension');
    }
}
