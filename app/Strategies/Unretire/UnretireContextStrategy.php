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
    private UnretireStrategyInterface $strategy;

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

        throw new \InvalidArgumentException('Could not find strategy for: ' . $model::class);
    }

    public function process(Carbon $unretiredAt = null): void
    {
        $this->strategy->unretire($unretiredAt);
    }
}
