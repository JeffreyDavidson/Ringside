<?php

namespace App\Strategies\Retirement;

use App\Exceptions\CannotBeRetiredException;
use App\Strategies\ClearInjury\ManagerClearInjuryStrategy;
use App\Strategies\Reinstate\ManagerReinstateStrategy;
use Carbon\Carbon;

class ManagerRetirementStrategy extends BaseRetirementStrategy implements RetirementStrategyInterface
{
    public function retire($model)
    {
        throw_unless($model->canBeRetired(), new CannotBeRetiredException);

        if ($model->isSuspended()) {
            ManagerReinstateStrategy::handle($model);
        }

        if ($model->isInjured()) {
            ManagerClearInjuryStrategy::handle($model);
        }

        $retiredDate = Carbon::parse($retiredAt)->toDateTimeString() ?: now()->toDateTimeString();

        $model->currentEmployment()->update(['ended_at' => $retiredDate]);
        $model->retirements()->create(['started_at' => $retiredDate]);
        $model->updateStatusAndSave();
    }
}
