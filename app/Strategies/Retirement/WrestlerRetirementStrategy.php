<?php

namespace App\Strategies\Retirement;

use App\Exceptions\CannotBeRetiredException;
use App\Strategies\ClearInjury\WrestlerClearInjuryStrategy;
use App\Strategies\Reinstate\WrestlerReinstateStrategy;
use Carbon\Carbon;

class WrestlerRetirementStrategy extends BaseRetirementStrategy implements RetirementStrategyInterface
{
    public function retire($model)
    {
        throw_unless($model->canBeRetired(), new CannotBeRetiredException);

        if ($model->isSuspended()) {
            WrestlerReinstateStrategy::handle($model);
        }

        if ($model->isInjured()) {
            WrestlerClearInjuryStrategy::handle($model);
        }

        $retiredDate = Carbon::parse($retiredAt)->toDateTimeString() ?: now()->toDateTimeString();

        $model->currentEmployment()->update(['ended_at' => $retiredDate]);
        $model->retirements()->create(['started_at' => $retiredDate]);
        $model->updateStatusAndSave();

        if ($model->currentTagTeam) {
            $model->currentTagTeam->updateStatusAndSave();
        }
    }
}
