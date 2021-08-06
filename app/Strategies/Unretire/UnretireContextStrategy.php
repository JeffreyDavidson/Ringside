<?php

namespace App\Strategies\Unretire;

use App\Models\Manager;
use App\Models\Referee;
use App\Models\TagTeam;
use App\Models\Title;
use App\Models\Wrestler;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class UnretireContextStrategy
{
    /**
     * The strategy to be used for the given model.
     *
     * @var \App\Strategies\Unretire\UnretireStrategyInterface
     */
    private UnretireStrategyInterface $strategy;

    /**
     * Create a new unretire context strategy instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     */
    public function __construct(Model $model)
    {
        if ($model instanceof Manager) {
            $this->strategy = new ManagerUnretireStrategy($model);
        } elseif ($model instanceof Referee) {
            $this->strategy = new RefereeUnretireStrategy($model);
        } elseif ($model instanceof TagTeam) {
            $this->strategy = new TagTeamUnretireStrategy($model);
        } elseif ($model instanceof Title) {
            $this->strategy = new TitleUnretireStrategy($model);
        } elseif ($model instanceof Wrestler) {
            $this->strategy = new WrestlerUnretireStrategy($model);
        }

        throw new \InvalidArgumentException('Could not find strategy for: '.$model::class);
    }

    /**
     * Process the unretire of the model.
     *
     * @param  \Carbon\Carbon|null $unretiredAt
     * @return void
     */
    public function process(Carbon $unretiredAt = null): void
    {
        $this->strategy->unretire($unretiredAt);
    }
}
