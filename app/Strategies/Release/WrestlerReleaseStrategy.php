<?php

namespace App\Strategies\Release;

use App\Exceptions\CannotBeReleasedException;
use App\Strategies\ClearInjury\WrestlerClearInjuryStrategy;
use App\Strategies\Reinstate\WrestlerReinstateStrategy;
use Carbon\Carbon;

class WrestlerReleaseStrategy extends BaseReleaseStrategy implements ReleaseStrategyInterface
{
    public function release($model)
    {
        throw_unless($model->canBeReleased(), new CannotBeReleasedException);

        if ($model->isSuspended()) {
            WrestlerReinstateStrategy::handle($model);
        }

        if ($model->isInjured()) {
            WrestlerClearInjuryStrategy::handle($model);
        }

        $releaseDate = Carbon::parse($releasedAt)->toDateTimeString() ?? now()->toDateTimeString();

        $model->currentEmployment->update(['ended_at' => $releaseDate]);
        $model->updateStatusAndSave();

        if ($model->currentTagTeam) {
            $model->currentTagTeam->updateStatusAndSave();
        }
    }
}
