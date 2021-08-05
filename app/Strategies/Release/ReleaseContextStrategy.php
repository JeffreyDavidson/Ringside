<?php

namespace App\Strategies\Release;

use App\Models\Manager;
use App\Models\Referee;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ReleaseContextStrategy
{
    private ReleaseStrategyInterface $strategy;

    public function __construct(Model $model)
    {
        if ($model instanceof Manager) {
            $this->strategy = new ManagerReleaseStrategy($model);
        } elseif ($model instanceof Referee) {
            $this->strategy = new RefereeReleaseStrategy($model);
        } elseif ($model instanceof TagTeam) {
            $this->strategy = new TagTeamReleaseStrategy($model);
        } elseif ($model instanceof Wrestler) {
            $this->strategy = new WrestlerReleaseStrategy($model);
        }

        throw new \InvalidArgumentException('Could not find strategy for: ' . $model::class);
    }

    public function process(Carbon $releasedAt = null): void
    {
        $this->strategy->release($releasedAt);
    }
}
