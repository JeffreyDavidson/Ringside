<?php

namespace App\Strategies\Retirement;

use App\Models\Manager;
use App\Models\Referee;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Model;

class RetirementContextStrategy
{
    private RetirementStrategyInterface $strategy;

    public function __construct(Model $model)
    {
        if ($model instanceof Manager) {
            $this->strategy = new ManagerRetirementStrategy($model);
        } elseif ($model instanceof Referee) {
            $this->strategy = new RefereeRetirementStrategy($model);
        } elseif ($model instanceof TagTeam) {
            $this->strategy = new TagTeamRetirementStrategy($model);
        } elseif ($model instanceof Wrestler) {
            $this->strategy = new WrestlerRetirementStrategy($model);
        }

        throw new \InvalidArgumentException('Could not find strategy for: ' . $model::class);
    }

    public function process(): void
    {
        $this->strategy->retire();
    }
}
