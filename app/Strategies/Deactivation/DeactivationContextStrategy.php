<?php

namespace App\Strategies\Deactivation;

use App\Models\Stable;
use App\Models\Title;
use Illuminate\Database\Eloquent\Model;

class DeactivationContextStrategy
{
    /**
     * The strategy to be used for the given model.
     *
     * @var \App\Strategies\Deactivation\DeactivationStrategyInterface
     */
    private DeactivationStrategyInterface $strategy;

    /**
     * Create a new deactivation context strategy instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     */
    public function __construct(Model $model)
    {
        if ($model instanceof Stable) {
            $this->strategy = new StableDeactivationStrategy($model);
        } elseif ($model instanceof Title) {
            $this->strategy = new TitleDeactivationStrategy($model);
        }

        throw new \InvalidArgumentException('Could not find strategy for: '.$model::class);
    }

    /**
     * Process the deactivation of the model.
     *
     * @param  string|null $deactivationDate
     * @return void
     */
    public function process(string $deactivationDate = null)
    {
        $this->strategy->deactivate($deactivationDate);
    }
}
