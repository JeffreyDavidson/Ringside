<?php

namespace App\Strategies\Retirement;

use App\Models\Manager;
use App\Models\Referee;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class RetirementContextStrategy
{
    /**
     * The strategy to be used for the given model.
     *
     * @var \App\Strategies\Retirement\RetirementStrategyInterface
     */
    private RetirementStrategyInterface $strategy;

    /**
     * Create a new retire context strategy instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     */
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

        throw new \InvalidArgumentException('Could not find strategy for: '.$model::class);
    }

    /**
     * Process the retire of the model.
     *
     * @param  \Carbon\Carbon|null $retiredAt
     * @return void
     */
    public function process(Carbon $retiredAt = null): void
    {
        $this->strategy->retire($retiredAt);
    }
}
