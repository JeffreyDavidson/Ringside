<?php

namespace App\Strategies\Unretire;

use App\Exceptions\CannotBeUnretiredException;
use App\Strategies\ClearInjury\WrestlerClearInjuryStrategy;
use App\Strategies\Reinstate\WrestlerReinstateStrategy;
use Carbon\Carbon;

class WrestlerUnretireStrategy extends BaseUnretireStrategy
{
    public function unretire($model)
    {
        throw_unless($model->canBeUnretired(), new CannotBeUnretiredException);

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
