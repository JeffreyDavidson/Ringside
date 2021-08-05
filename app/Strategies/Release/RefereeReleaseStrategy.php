<?php

namespace App\Strategies\Release;

use App\Exceptions\CannotBeReleasedException;
use App\Strategies\ClearInjury\RefereeClearInjuryStrategy;
use App\Strategies\Reinstate\RefereeReinstateStrategy;
use Carbon\Carbon;

class RefereeReleaseStrategy extends BaseReleaseStrategy implements ReleaseStrategyInterface
{
    public function release($model)
    {
        throw_unless($model->canBeReleased(), new CannotBeReleasedException);

        if ($model->isSuspended()) {
            RefereeReinstateStrategy::handle($model);
        }

        if ($model->isInjured()) {
            RefereeClearInjuryStrategy::handle($model);
        }

        $releaseDate = Carbon::parse($releasedAt)->toDateTimeString() ?? now()->toDateTimeString();

        $model->currentEmployment->update(['ended_at' => $releaseDate]);
        $model->updateStatusAndSave();
    }
}
