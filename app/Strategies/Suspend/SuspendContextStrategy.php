<?php

namespace App\Strategies\Suspend;

use App\Models\Manager;
use App\Models\Referee;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class SuspendContextStrategy
{
    /**
     * The strategy to be used for the given model.
     *
     * @var \App\Strategies\Suspend\SuspendStrategyInterface
     */
    private SuspendStrategyInterface $strategy;

    /**
     * Create a new suspend context strategy instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     */
    public function __construct(Model $model)
    {
        if ($model instanceof Manager) {
            $this->strategy = new ManagerSuspendStrategy($model);
        } elseif ($model instanceof Referee) {
            $this->strategy = new RefereeSuspendStrategy($model);
        } elseif ($model instanceof TagTeam) {
            $this->strategy = new TagTeamSuspendStrategy($model);
        } elseif ($model instanceof Wrestler) {
            $this->strategy = new WrestlerSuspendStrategy($model);
        }

        throw new \InvalidArgumentException('Could not find strategy for: ' . $model::class);
    }

    /**
     * Process the suspend of the model.
     *
     * @param  \Carbon\Carbon|null $unretiredAt
     * @return void
     */
    public function process(Carbon $suspendedAt = null): void
    {
        $this->strategy->suspend($suspendedAt);
    }
}
