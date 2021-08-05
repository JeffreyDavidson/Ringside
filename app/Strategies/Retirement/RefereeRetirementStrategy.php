<?php

namespace App\Strategies\Retirement;

use App\Exceptions\CannotBeRetiredException;
use App\Strategies\ClearInjury\RefereeClearInjuryStrategy;
use App\Strategies\Reinstate\RefereeReinstateStrategy;
use Carbon\Carbon;

class RefereeRetirementStrategy extends BaseRetirementStrategy implements RetirementStrategyInterface
{
    public function retire($model)
    {
        throw_unless($model->canBeRetired(), new CannotBeRetiredException);

        if ($model->isSuspended()) {
            RefereeReinstateStrategy::handle($model);
        }

        if ($model->isInjured()) {
            RefereeClearInjuryStrategy::handle($model);
        }

        $retiredDate = Carbon::parse($retiredAt)->toDateTimeString() ?: now()->toDateTimeString();

        $model->currentEmployment()->update(['ended_at' => $retiredDate]);
        $model->retirements()->create(['started_at' => $retiredDate]);
        $model->updateStatusAndSave();
    }
}
