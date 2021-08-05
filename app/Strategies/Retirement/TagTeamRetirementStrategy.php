<?php

namespace App\Strategies\Retirement;

use App\Exceptions\CannotBeRetiredException;
use App\Strategies\Reinstate\TagTeamReinstateStrategy;

class TagTeamRetirementStrategy extends BaseRetirementStrategy implements RetirementStrategyInterface
{
    public function retire($model)
    {
        throw_unless($model->canBeRetired(), new CannotBeRetiredException);

        $retiredDate = $retiredAt ?: now();

        if ($model->isSuspended()) {
            TagTeamReinstateStrategy::handle($model);
        }

        $model->currentEmployment()->update(['ended_at' => $retiredDate]);
        $model->retirements()->create(['started_at' => $retiredDate]);
        $model->currentWrestlers->each->retire($retiredDate);
        $model->updateStatusAndSave();
    }
}
