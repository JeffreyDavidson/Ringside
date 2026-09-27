<?php

declare(strict_types=1);

namespace App\Builders\Concerns;

trait FiltersByInactiveActivity
{
    protected function whereInactiveActivity(): static
    {
        return $this->whereHas('previousActivityPeriods')
            ->whereDoesntHave('currentActivityPeriod')
            ->whereDoesntHave('futureActivityPeriod')
            ->whereDoesntHave('currentRetirement');
    }
}
