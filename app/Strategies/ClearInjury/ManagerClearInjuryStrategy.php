<?php

namespace App\Strategies\ClearInjury;

use App\Exceptions\CannotBeClearedFromInjuryException;
use Carbon\Carbon;

class ManagerClearInjuryStrategy extends BaseClearInjuryStrategy implements ClearInjuryStrategyInterface
{
    public function clearInjury($model)
    {
        throw_unless($model->canBeClearedFromInjury(), new CannotBeClearedFromInjuryException);

        $recoveryDate = Carbon::parse($recoveredAt)->toDateTImeString() ?? now()->toDateTimeString();

        $model->currentInjury()->update(['ended_at' => $recoveryDate]);
        $model->updateStatusAndSave();
    }
}
