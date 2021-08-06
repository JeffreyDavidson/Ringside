<?php

namespace App\Strategies\Retirement;

use App\Exceptions\CannotBeRetiredException;
use App\Models\Contracts\Retirable;
use Carbon\Carbon;

class StableRetirementStrategy extends BaseRetirementStrategy implements RetirementStrategyInterface
{
    /**
     * The interface implementation.
     *
     * @var \App\Models\Contracts\Retirable
     */
    private Retirable $retirable;

    /**
     * Create a new stable retirement strategy instance.
     *
     * @param \App\Models\Contracts\Retirable $retirable
     */
    public function __construct(Retirable $retirable)
    {
        $this->retirable = $retirable;
    }

    /**
     * Retire a retirable model.
     *
     * @param  \Carbon\Carbon|null $retiredAt
     * @return void
     */
    public function retire(Carbon $retiredAt = null)
    {
        throw_unless($this->retirable->canBeRetired(), new CannotBeRetiredException);

        $retiredDate = $retiredAt ?: now();

        $this->retirable->currentActivation()->update(['ended_at' => $retiredDate]);
        $this->retirable->retirements()->create(['started_at' => now()]);
        $this->retirable->currentWrestlers->each->retire($retiredDate);
        $this->retirable->currentTagTeams->each->retire();
        $this->retirable->updateStatusAndSave();
    }
}
