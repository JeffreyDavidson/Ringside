<?php

declare(strict_types=1);

namespace App\Builders\Concerns;

trait HasEmploymentScopes
{
    public function unemployed(): static
    {
        return $this->whereDoesntHave('currentEmployment')
            ->whereDoesntHave('previousEmployments')
            ->whereDoesntHave('futureEmployment');
    }

    public function employed(): static
    {
        return $this->whereHas('currentEmployment');
    }

    public function released(): static
    {
        return $this->whereHas('previousEmployments')
            ->whereDoesntHave('currentEmployment')
            ->whereDoesntHave('futureEmployment')
            ->whereDoesntHave('currentRetirement');
    }

    public function futureEmployed(): static
    {
        return $this->whereHas('futureEmployment');
    }
}
