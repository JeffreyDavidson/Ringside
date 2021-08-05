<?php

namespace App\Strategies\Retirement;

use App\Exceptions\CannotBeRetiredException;
use App\Models\Contracts\Retirable;
use Carbon\Carbon;

class TitleRetirementStrategy extends BaseRetirementStrategy implements RetirementStrategyInterface
{
    private Retirable $retirable;

    public function __construct(Retirable $retirable)
    {
        $this->retirable = $retirable;
    }

    public function retire(Carbon $retiredAt = null)
    {
        throw_unless($this->retirable->canBeRetired(), new CannotBeRetiredException);

        $retiredDate = $retiredAt ?: now();

        $this->retirable->currentActivation()->update(['ended_at' => $retiredDate]);
        $this->retirable->retirements()->create(['started_at' => $retiredDate]);
        $this->retirable->updateStatusAndSave();
    }
}
