<?php

namespace App\Strategies\Employment;

use App\Models\Manager;
use App\Models\Referee;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Model;

class EmploymentContextStrategy
{
    /**
     * The strategy to be used for the given model.
     *
     * @var \App\Strategies\Employment\EmploymentStrategyInterface
     */
    private EmploymentStrategyInterface $strategy;

    /**
     * Create a new employment context strategy instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     */
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

        throw new \InvalidArgumentException('Could not find strategy for: '.$model::class);
    }

    /**
     * Process the employment of the model.
     *
     * @param  string|null $startedAtDate
     * @return void
     */
    public function process(string $startedAtDate = null)
    {
        $this->strategy->employ($startedAtDate);
    }
}
