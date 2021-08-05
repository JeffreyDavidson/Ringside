<?php

namespace App\Strategies\Release;

use App\Exceptions\CannotBeReleasedException;
use App\Strategies\ClearInjury\ManagerClearInjuryStrategy;
use App\Strategies\Reinstate\ManagerReinstateStrategy;
use Carbon\Carbon;

class ManagerReleaseStrategy extends BaseReleaseStrategy implements ReleaseStrategyInterface
{
    public function release($model)
    {
        throw_unless($model->canBeReleased(), new CannotBeReleasedException);

        if ($model->isSuspended()) {
            ManagerReinstateStrategy::handle($model);
        }

        if ($model->isInjured()) {
            ManagerClearInjuryStrategy::handle($model);
        }

        $releaseDate = Carbon::parse($releasedAt)->toDateTimeString() ?? now()->toDateTimeString();

        $model->currentEmployment->update(['ended_at' => $releaseDate]);
        $model->updateStatusAndSave();

        if ($model->currentTagTeam) {
            $model->currentTagTeam->updateStatusAndSave();
        }
    }
}
