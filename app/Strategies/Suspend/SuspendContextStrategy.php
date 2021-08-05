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
    private SuspendStrategyInterface $strategy;

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

    public function process(Carbon $suspendedAt = null): void
    {
        $this->strategy->suspend($suspendedAt);
    }
}
