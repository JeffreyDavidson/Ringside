<?php

namespace App\Strategies\ClearInjury;

use App\Models\Manager;
use App\Models\Referee;
use App\Models\Wrestler;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ClearInjuryContextStrategy
{
    private ClearInjuryStrategyInterface $strategy;

    public function __construct(Model $model)
    {
        if ($model instanceof Manager) {
            $this->strategy = new ManagerClearInjuryStrategy($model);
        } elseif ($model instanceof Referee) {
            $this->strategy = new RefereeClearInjuryStrategy($model);
        } elseif ($model instanceof Wrestler) {
            $this->strategy = new WrestlerClearInjuryStrategy($model);
        }

        throw new \InvalidArgumentException('Could not find strategy for: ' . $model::class);
    }

    public function process(Carbon $recoveredAt = null): void
    {
        $this->strategy->clearInjury($recoveredAt);
    }
}
