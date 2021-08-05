<?php

namespace App\Strategies\Injure;

use App\Exceptions\CannotBeInjuredException;
use Carbon\Carbon;

class ManagerInjuryStrategy extends BaseInjuryStrategy implements InjuryStrategyInterface
{
    public function injure($model)
    {
        throw_unless($model->canBeInjured(), new CannotBeInjuredException);

        $injuredDate = Carbon::parse($injuredAt)->toDateTimeString() ?? now()->toDateTimeString();

        $model->injuries()->create(['started_at' => $injuredDate]);
        $model->updateStatusAndSave();
    }
}
