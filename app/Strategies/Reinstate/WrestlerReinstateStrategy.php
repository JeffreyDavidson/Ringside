<?php

namespace App\Strategies\Reinstate;

use App\Exceptions\CannotBeReinstatedException;
use Carbon\Carbon;

class WrestlerReinstateStrategy extends BaseReinstateStrategy implements ReinstateStrategyInterface
{
    public function reinstate($model)
    {
        throw_unless($model->canBeReinstated(), new CannotBeReinstatedException);

        $reinstatedDate = Carbon::parse($reinstatedAt)->toDateTimeString() ?: now()->toDateTimeString();

        $model->currentSuspension()->update(['ended_at' => $reinstatedDate]);
        $model->updateStatusAndSave();

        if ($model->currentTagTeam) {
            $model->currentTagTeam->updateStatusAndSave();
        }
    }
}
