<?php

declare(strict_types=1);

namespace App\Builders\Concerns;

trait LoadsFirstEmployment
{
    public function withFirstEmployment(): static
    {
        return $this->with('firstEmployment');
    }
}
