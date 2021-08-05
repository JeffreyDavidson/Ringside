<?php

namespace App\Strategies\Activation;

use Carbon\Carbon;
use App\Models\Title;
use App\Models\Stable;
use App\Models\Contracts\Activatable;
use Illuminate\Database\Eloquent\Model;

class ActivationContextStrategy
{
    private ActivationStrategyInterface $strategy;

    public function __construct(Model $model)
    {
        if ($model instanceof Stable) {
            $this->strategy = new StableActivationStrategy($model);
        } elseif ($model instanceof Title) {
            $this->strategy = new TitleActivationStrategy($model);
        }

        throw new \InvalidArgumentException('Could not find strategy for: ' . $model::class);
    }

    public function process(Carbon $activatedAtDate = null): void
    {
        $this->strategy->activate($activatedAtDate);
    }
}
