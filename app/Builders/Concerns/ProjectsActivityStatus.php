<?php

declare(strict_types=1);

namespace App\Builders\Concerns;

trait ProjectsActivityStatus
{
    public function withActivityStatusState(): static
    {
        return $this->withExists([
            'currentRetirement as status_current_retirement_exists',
            'currentActivityPeriod as status_current_activity_period_exists',
            'futureActivityPeriod as status_future_activity_period_exists',
            'activityPeriods as status_activity_periods_exists',
        ]);
    }
}
