<?php

namespace App\Strategies\ClearInjury;

use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Models\Contracts\Injurable;
use Carbon\Carbon;

class ManagerClearInjuryStrategy extends BaseClearInjuryStrategy implements ClearInjuryStrategyInterface
{
    private Injurable $injurable;

    public function __construct(Injurable $injurable)
    {
        $this->injurable = $injurable;
    }

    public function clearInjury(Carbon $recoveredAt = null)
    {
        throw_unless($this->injurable->canBeClearedFromInjury(), new CannotBeClearedFromInjuryException);

        $recoveryDate = Carbon::parse($recoveredAt)->toDateTImeString() ?? now()->toDateTimeString();

        $this->injurable->currentInjury()->update(['ended_at' => $recoveryDate]);
        $this->injurable->updateStatusAndSave();
    }
}
