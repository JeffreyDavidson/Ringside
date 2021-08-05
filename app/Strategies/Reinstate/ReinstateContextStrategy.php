<?php

namespace App\Strategies\Reinstate;

use App\Models\Manager;
use App\Models\Referee;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ReinstateContextStrategy
{
    private ReinstateStrategyInterface $strategy;

    public function __construct(Model $model)
    {
        if ($model instanceof Manager) {
            $this->strategy = new ManagerReinstateStrategy($model);
        } elseif ($model instanceof Referee) {
            $this->strategy = new RefereeReinstateStrategy($model);
        }  elseif ($model instanceof TagTeam) {
            $this->strategy = new TagTeamReinstateStrategy($model);
        } elseif ($model instanceof Wrestler) {
            $this->strategy = new WrestlerReinstateStrategy($model);
        }

        throw new \InvalidArgumentException('Could not find strategy for: ' . $model::class);
    }

    public function process(Carbon $reinstatedAt = null): void
    {
        $this->strategy->reinstate($reinstatedAt);
    }
}
