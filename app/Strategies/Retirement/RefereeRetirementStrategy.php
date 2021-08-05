<?php

namespace App\Strategies\Retirement;

use App\Exceptions\CannotBeRetiredException;
use App\Models\Contracts\Retirable;
use App\Strategies\ClearInjury\RefereeClearInjuryStrategy;
use App\Strategies\Reinstate\RefereeReinstateStrategy;
use Carbon\Carbon;

class RefereeRetirementStrategy extends BaseRetirementStrategy implements RetirementStrategyInterface
{
    private Retirable $retirable;

    public function __construct(Retirable $retirable)
    {
        $this->retirable = $retirable;
    }

    public function retire(Carbon $retiredAt = null)
    {
        throw_unless($this->retirable->canBeRetired(), new CannotBeRetiredException);

        if ($this->retirable->isSuspended()) {
            RefereeReinstateStrategy::handle($this->retirable);
        }

        if ($this->retirable->isInjured()) {
            RefereeClearInjuryStrategy::handle($this->retirable);
        }

        $retiredDate = Carbon::parse($retiredAt)->toDateTimeString() ?: now()->toDateTimeString();

        $this->retirable->currentEmployment()->update(['ended_at' => $retiredDate]);
        $this->retirable->retirements()->create(['started_at' => $retiredDate]);
        $this->retirable->updateStatusAndSave();
    }
}
