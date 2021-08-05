<?php

namespace App\Strategies\Retirement;

use App\Exceptions\CannotBeRetiredException;

class TitleRetirementStrategy extends BaseRetirementStrategy implements RetirementStrategyInterface
{
    public function retire($model)
    {
        throw_unless($model->canBeRetired(), new CannotBeRetiredException);

        $retiredDate = $retiredAt ?: now();

        $model->currentActivation()->update(['ended_at' => $retiredDate]);
        $model->retirements()->create(['started_at' => $retiredDate]);
        $model->updateStatusAndSave();
    }
}
