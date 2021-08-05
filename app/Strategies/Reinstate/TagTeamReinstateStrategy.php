<?php

namespace App\Strategies\Reinstate;

use App\Exceptions\CannotBeReinstatedException;

class TagTeamReinstateStrategy extends BaseReinstateStrategy implements ReinstateStrategyInterface
{
    public function reinstate($model)
    {
        throw_unless($model->canBeReinstated(), new CannotBeReinstatedException);

        $reinstatedDate = $reinstatedAt ?: now();

        $model->currentSuspension()->update(['ended_at' => $reinstatedDate]);
        $model->currentWrestlers->each->reinstate($reinstatedDate);
        $model->updateStatusAndSave();
    }
}
