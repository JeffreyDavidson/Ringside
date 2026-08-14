<?php

declare(strict_types=1);

namespace App\Builders\Concerns;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

trait HasActivityPeriodScopes
{
    public function currentlyActive(): static
    {
        return $this->whereHas('currentActivityPeriod');
    }

    public function currentlyInactive(): static
    {
        return $this->whereDoesntHave('currentActivityPeriod');
    }

    public function activeDuring(Carbon $start, Carbon $end): static
    {
        return $this->whereHas('activityPeriods', function (Builder $activityPeriodQuery) use ($start, $end) {
            $activityPeriodQuery->where('started_at', '<=', $end)
                ->where(function (Builder $endDateQuery) use ($start) {
                    $endDateQuery->whereNull('ended_at')
                        ->orWhere('ended_at', '>=', $start);
                });
        });
    }

    public function activatedAfter(Carbon $date): static
    {
        return $this->whereHas('activityPeriods', fn (Builder $query) => $query->where('started_at', '>', $date));
    }

    public function activatedBefore(Carbon $date): static
    {
        return $this->whereHas('activityPeriods', fn (Builder $query) => $query->where('started_at', '<', $date));
    }

    public function deactivatedAfter(Carbon $date): static
    {
        return $this->whereHas('previousActivityPeriods', fn (Builder $query) => $query->where('ended_at', '>', $date));
    }

    public function neverActivated(): static
    {
        return $this->whereDoesntHave('activityPeriods');
    }

    public function withMultiplePeriods(int $minimumPeriods = 2): static
    {
        return $this->has('activityPeriods', '>=', $minimumPeriods);
    }
}
