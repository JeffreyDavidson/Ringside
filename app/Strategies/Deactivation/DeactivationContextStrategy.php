<?php

namespace App\Strategies\Deactivation;

use App\Models\Stable;
use App\Models\Title;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class DeactivationContextStrategy
{
    private DeactivationStrategyInterface $strategy;

    public function __construct(Model $model)
    {
        if ($model instanceof Stable) {
            $this->strategy = new StableDeactivationStrategy($model);
        } elseif ($model instanceof Title) {
            $this->strategy = new TitleDeactivationStrategy($model);
        }

        throw new \InvalidArgumentException('Could not find strategy for: ' . $model::class);
    }

    public function process(Carbon $startedAt = null): void
    {
        $this->strategy->deactivate($startedAt);
    }
}
