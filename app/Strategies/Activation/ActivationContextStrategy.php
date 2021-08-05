<?php

namespace App\Strategies\Activation;

use App\Models\Title;
use App\Models\Stable;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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
