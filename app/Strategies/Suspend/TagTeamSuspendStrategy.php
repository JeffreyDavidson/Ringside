<?php

namespace App\Strategies\Suspend;

use App\Exceptions\CannotBeSuspendedException;

class TagTeamSuspendStrategy extends BaseSuspendStrategy implements SuspendStrategyInterface
{
    public function suspend($model)
    {
        throw_unless($model->canBeSuspended(), new CannotBeSuspendedException);

        $suspendedDate = $suspendedAt ?: now();

        $model->suspensions()->create(['started_at' => $suspendedDate]);
        $model->currentWrestlers->each->suspend($suspendedDate);
        $model->updateStatusAndSave();
    }
}
