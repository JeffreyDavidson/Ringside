<?php

declare(strict_types=1);

namespace App\Builders\Concerns;

trait FiltersByRetirementStatus
{
    public function retired(): static
    {
        return $this->whereHas('currentRetirement');
    }
}
