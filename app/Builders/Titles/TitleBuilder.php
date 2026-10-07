<?php

declare(strict_types=1);

namespace App\Builders\Titles;

use App\Builders\Concerns\FiltersByInactiveActivity;
use App\Builders\Concerns\FiltersByName;
use App\Builders\Concerns\FiltersByNameInPromotion;
use App\Builders\Concerns\FiltersByRetirementStatus;
use App\Builders\Concerns\LoadsFirstActivityPeriod;
use App\Builders\Concerns\ProjectsActivityStatus;
use App\Enums\Titles\TitleStatus;
use App\Enums\Titles\TitleType;
use App\Models\Titles\Title;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of Title
 *
 * @extends Builder<TModel>
 */
class TitleBuilder extends Builder
{
    use FiltersByInactiveActivity;
    use FiltersByName;
    use FiltersByNameInPromotion;
    use FiltersByRetirementStatus;
    use LoadsFirstActivityPeriod;
    use ProjectsActivityStatus;

    public function whereType(TitleType $type): static
    {
        return $this->where('type', $type->value);
    }

    public function whereStatus(TitleStatus $status): static
    {
        return match ($status) {
            TitleStatus::Undebuted => $this->undebuted(),
            TitleStatus::PendingDebut => $this->withPendingDebut(),
            TitleStatus::Active => $this->active(),
            TitleStatus::Inactive => $this->inactive(),
            TitleStatus::Retired => $this->retired(),
        };
    }

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
        return $this->whereInactiveActivity();
    }

    public function withPendingDebut(): static
    {
        return $this->whereHas('futureActivityPeriod')
            ->whereDoesntHave('currentRetirement');
    }

    /**
     * Titles a booking form offers for a promotion (null for titles without one), plus ids already chosen even when
     * they belong to another promotion.
     *
     * @param  array<int, mixed>  $selectedIds
     */
    public function offeredForPromotion(?int $promotionId, array $selectedIds = []): static
    {
        return $this->where(fn (Builder $query): Builder => $query
            ->where('promotion_id', $promotionId)
            ->orWhereKey($selectedIds));
    }
}
