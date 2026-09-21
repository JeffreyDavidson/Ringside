<?php

declare(strict_types=1);

namespace App\Builders\Concerns;

trait LoadsFirstActivityPeriod
{
    public function withFirstActivityPeriod(): static
    {
        return $this->with('firstActivityPeriod');
    }
}
