<?php

namespace App\Strategies\Unretire;

use App\Exceptions\CannotBeUnretiredException;
use Carbon\Carbon;

class RefereeUnretireStrategy extends BaseUnretireStrategy implements UnretireStrategyInterface
{
    public function unretire($model)
    {
        throw_unless($model->canBeUnretired(), new CannotBeUnretiredException);

        $unretiredDate = Carbon::parse($unretiredAt)->toDateTimeString() ?: now()->toDateTimeString();

        $model->currentRetirement()->update(['ended_at' => $unretiredDate]);
        $model->employments()->create(['started_at' => $unretiredDate]);
        $model->updateStatusAndSave();
    }
}
