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
    /**
     * The strategy to be used for the given model.
     *
     * @var \App\Strategies\Release\ReleaseStrategyInterface
     */
    private ReleaseStrategyInterface $strategy;

    /**
     * Create a new release context strategy instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     */
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

    /**
     * Process the release of the model.
     *
     * @param  \Carbon\Carbon|null $releasedAt
     * @return void
     */
    public function process(Carbon $releasedAt = null): void
    {
        $this->strategy->release($releasedAt);
    }
}
