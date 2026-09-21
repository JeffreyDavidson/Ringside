<?php

declare(strict_types=1);

namespace App\Builders\Concerns;

trait FiltersByName
{
    public function whereName(string $name): static
    {
        return $this->where('name', $name);
    }
}
