<?php

namespace App\Strategies\Retirement;

use App\Exceptions\CannotBeRetiredException;
use App\Models\Contracts\Retirable;
use App\Strategies\ClearInjury\WrestlerClearInjuryStrategy;
use App\Strategies\Reinstate\WrestlerReinstateStrategy;

class WrestlerRetirementStrategy extends BaseRetirementStrategy implements RetirementStrategyInterface
{
    /**
     * The interface implementation.
     *
     * @var \App\Models\Contracts\Retirable
     */
    private Retirable $retirable;

    /**
     * Create a new wrestler retirement strategy instance.
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
     * @param  string|null $retiredAt
     * @return void
     */
    public function retire(string $retiredAt = null)
    {
        throw_unless($this->retirable->canBeRetired(), new CannotBeRetiredException);

        if ($this->retirable->isSuspended()) {
            (new WrestlerReinstateStrategy($this->retirable))->reinstate();
        }

        if ($this->retirable->isInjured()) {
            (new WrestlerClearInjuryStrategy($this->retirable))->clearInjury();
        }

        $retiredDate = $retiredAt ?: now()->toDateTimeString();

        $this->repository->release($this->retirable, $retiredDate);
        $this->repository->retire($this->retirable, $retiredDate);
        $this->retirable->updateStatusAndSave();

        if ($this->retirable->currentTagTeam) {
            $this->retirable->currentTagTeam->updateStatusAndSave();
        }
    }
}
