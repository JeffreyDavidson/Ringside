<?php

namespace App\Strategies\Employment;

use App\Models\Manager;
use App\Models\Referee;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Model;

class EmploymentContextStrategy
{
    private EmploymentStrategyInterface $strategy;

    public function __construct(Model $model)
    {
        if ($model instanceof Manager) {
            $this->strategy = new ManagerEmploymentStrategy($model);
        } elseif ($model instanceof Referee) {
            $this->strategy = new RefereeEmploymentStrategy($model);
        } elseif ($model instanceof TagTeam) {
            $this->strategy = new TagTeamEmploymentStrategy($model);
        } elseif ($model instanceof Wrestler) {
            $this->strategy = new WrestlerEmploymentStrategy($model);
        }

        throw new \InvalidArgumentException('Could not find strategy for: ' . $model::class);
    }

    public function process(): void
    {
        $this->strategy->employ();
    }
}
