<?php

namespace App\Strategies\Employment;

use App\Exceptions\CannotBeEmployedException;
use Carbon\Carbon;

class TagTeamEmploymentStrategy extends BaseEmploymentStrategy implements EmploymentStrategyInterface
{
    public function employ($model)
    {
        throw_unless($model->canBeEmployed(), new CannotBeEmployedException);

        $startAtDate = Carbon::parse($startAtDate)->toDateTimeString('minute') ?? now()->toDateTimeString('minute');

        $model->employments()->updateOrCreate(['ended_at' => null], ['started_at' => $startAtDate]);

        if ($model->currentWrestlers->every->isNotInEmployment()) {
            $model->currentWrestlers->each->employ($startAtDate);
        }

        $model->updateStatusAndSave();
    }
}
