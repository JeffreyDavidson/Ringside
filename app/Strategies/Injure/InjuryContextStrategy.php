<?php

namespace App\Strategies\Injure;

use App\Models\Manager;
use App\Models\Referee;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Model;

class InjuryContextStrategy
{
    private InjuryStrategyInterface $strategy;

    public function __construct(Model $model)
    {
        if ($model instanceof Manager) {
            $this->strategy = new ManagerInjuryStrategy($model);
        } elseif ($model instanceof Referee) {
            $this->strategy = new RefereeInjuryStrategy($model);
        } elseif ($model instanceof Wrestler) {
            $this->strategy = new WrestlerInjuryStrategy($model);
        }

        throw new \InvalidArgumentException('Could not find strategy for: ' . $model::class);
    }

    public function process(): void
    {
        $this->strategy->injury();
    }
}
