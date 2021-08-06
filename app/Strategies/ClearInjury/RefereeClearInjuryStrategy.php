<?php

namespace App\Strategies\ClearInjury;

use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Models\Contracts\Injurable;
use Carbon\Carbon;

class RefereeClearInjuryStrategy extends BaseClearInjuryStrategy implements ClearInjuryStrategyInterface
{
    /**
     * The interface implementation.
     *
     * @var \App\Models\Contracts\Injurable
     */
    private Injurable $injurable;

    /**
     * Create a new referee clear injury strategy instance.
     *
     * @param \App\Models\Contracts\Injurable $injurable
     */
    public function __construct(Injurable $injurable)
    {
        $this->injurable = $injurable;
    }

    /**
     * Clear an injury of an injurable model.
     *
     * @param  \Carbon\Carbon|null $recoveredAt
     * @return void
     */
    public function clearInjury(Carbon $recoveredAt = null)
    {
        throw_unless($this->injurable->canBeClearedFromInjury(), new CannotBeClearedFromInjuryException);

        $recoveryDate = Carbon::parse($recoveredAt)->toDateTImeString() ?? now()->toDateTimeString();

        $this->injurable->currentInjury()->update(['ended_at' => $recoveryDate]);
        $this->injurable->updateStatusAndSave();
    }
}
