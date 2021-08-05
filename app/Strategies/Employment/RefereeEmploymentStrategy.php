<?php

namespace App\Strategies\Employment;

use App\Exceptions\CannotBeEmployedException;
use Carbon\Carbon;

class RefereeEmploymentStrategy extends BaseEmploymentStrategy implements EmploymentStrategyInterface
{
    public function employ($model)
    {
        throw_unless($model->canBeEmployed(), new CannotBeEmployedException);

        $startDate = Carbon::parse($startedAt)->toDateTimeString() ?? now()->toDateTimeString();

        $model->employments()->updateOrCreate(['ended_at' => null], ['started_at' => $startDate]);
        $model->updateStatusAndSave();
    }
}
