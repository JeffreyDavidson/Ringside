<?php

namespace App\Strategies\Unretire;

use App\Exceptions\CannotBeUnretiredException;

class TagTeamUnretireStrategy extends BaseUnretireStrategy implements UnretireStrategyInterface
{
    public function unretire($model)
    {
        throw_unless($model->canBeUnretired(), new CannotBeUnretiredException);

        $unretiredDate = $unretiredAt ?: now();

        $model->currentRetirement()->update(['ended_at' => $unretiredDate]);
        $model->currentWrestlers->each->unretire($unretiredDate);
        $model->updateStatusAndSave();

        $model->employ($unretiredDate);
        $model->updateStatusAndSave();
    }
}
