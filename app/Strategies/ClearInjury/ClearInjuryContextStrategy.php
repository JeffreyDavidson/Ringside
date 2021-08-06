<?php

namespace App\Strategies\ClearInjury;

use App\Models\Manager;
use App\Models\Referee;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Model;

class ClearInjuryContextStrategy
{
    /**
     * The strategy to be used for the given model.
     *
     * @var \App\Strategies\ClearInjury\ClearInjuryStrategyInterface
     */
    private ClearInjuryStrategyInterface $strategy;

    /**
     * Create a new clear injury context strategy instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     */
    public function __construct(Model $model)
    {
        if ($model instanceof Manager) {
            $this->strategy = new ManagerClearInjuryStrategy($model);
        } elseif ($model instanceof Referee) {
            $this->strategy = new RefereeClearInjuryStrategy($model);
        } elseif ($model instanceof Wrestler) {
            $this->strategy = new WrestlerClearInjuryStrategy($model);
        }

        throw new \InvalidArgumentException('Could not find strategy for: '.$model::class);
    }

    /**
     * Process the clearing of the injury of the model.
     *
     * @param  string|null $recoveredAt
     * @return void
     */
    public function process($recoveredAt = null)
    {
        $this->strategy->clearInjury($recoveredAt);
    }
}
