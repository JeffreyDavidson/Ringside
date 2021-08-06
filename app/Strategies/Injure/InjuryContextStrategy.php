<?php

namespace App\Strategies\Injure;

use App\Models\Manager;
use App\Models\Referee;
use App\Models\Wrestler;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class InjuryContextStrategy
{
    /**
     * The strategy to be used for the given model.
     *
     * @var \App\Strategies\Injure\InjuryStrategyInterface
     */
    private InjuryStrategyInterface $strategy;

    /**
     * Create a new injury context strategy instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     */
    public function __construct(Model $model)
    {
        if ($model instanceof Manager) {
            $this->strategy = new ManagerInjuryStrategy($model);
        } elseif ($model instanceof Referee) {
            $this->strategy = new RefereeInjuryStrategy($model);
        } elseif ($model instanceof Wrestler) {
            $this->strategy = new WrestlerInjuryStrategy($model);
        }

        throw new \InvalidArgumentException('Could not find strategy for: '.$model::class);
    }

    /**
     * Process the injury of the model.
     *
     * @param  string|null $injuredAt
     * @return void
     */
    public function process(string $injuredAt = null)
    {
        $this->strategy->injure($injuredAt);
    }
}
