<?php

namespace App\Strategies\Activation;

use App\Models\Stable;
use App\Models\Title;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ActivationContextStrategy
{
    /**
     * The strategy to be used for the given model.
     *
     * @var \App\Strategies\Activation\ActivationStrategyInterface
     */
    private ActivationStrategyInterface $strategy;

    /**
     * Create a new activation context strategy instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     */
    public function __construct(Model $model)
    {
        if ($model instanceof Stable) {
            $this->strategy = new StableActivationStrategy($model);
        } elseif ($model instanceof Title) {
            $this->strategy = new TitleActivationStrategy($model);
        }

        throw new \InvalidArgumentException('Could not find strategy for: '.$model::class);
    }

    /**
     * Process the activation of the model.
     *
     * @param  \Carbon\Carbon|null $activatedAtDate
     * @return void
     */
    public function process(Carbon $activatedAtDate = null): void
    {
        $this->strategy->activate($activatedAtDate);
    }
}
