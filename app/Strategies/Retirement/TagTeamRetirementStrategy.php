<?php

namespace App\Strategies\Retirement;

use App\Exceptions\CannotBeRetiredException;
use App\Models\Contracts\Retirable;
use App\Strategies\Reinstate\TagTeamReinstateStrategy;
use Carbon\Carbon;

class TagTeamRetirementStrategy extends BaseRetirementStrategy implements RetirementStrategyInterface
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

        if ($this->retirable->isSuspended()) {
            TagTeamReinstateStrategy::handle($this->retirable);
        }

        $this->retirable->currentEmployment()->update(['ended_at' => $retiredDate]);
        $this->retirable->retirements()->create(['started_at' => $retiredDate]);
        $this->retirable->currentWrestlers->each->retire($retiredDate);
        $this->retirable->updateStatusAndSave();
    }
}
