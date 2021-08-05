<?php

namespace App\Strategies\Deactivation;

use App\Models\Contracts\Activatable;
use App\Models\Stable;
use App\Models\Title;

class DeactivationContextStrategy
{
    private DeactivationStrategyInterface $strategy;

    public function __construct(Activatable $model)
    {
        if ($model instanceof Stable) {
            $this->strategy = new StableDeactivationStrategy($model);
        } elseif ($model instanceof Title) {
            $this->strategy = new TitleDeactivationStrategy($model);
        }

        throw new \InvalidArgumentException('Could not find strategy for: ' . $model::class);
    }

    public function process(): void
    {
        $this->strategy->deactivate();
    }
}
