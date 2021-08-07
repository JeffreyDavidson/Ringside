<?php

namespace App\Strategies\Retirement;

use App\Exceptions\CannotBeRetiredException;
use App\Models\Contracts\Retirable;
use App\Repositories\WrestlerRepository;
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
     * The repository implementation.
     *
     * @var \App\Repositories\WrestlerRepository
     */
    private WrestlerRepository $wrestlerRepository;

    /**
     * Create a new wrestler retirement strategy instance.
     *
     * @param \App\Models\Contracts\Retirable $retirable
     */
    public function __construct(Retirable $retirable)
    {
        $this->retirable = $retirable;
        $this->wrestlerRepository = new WrestlerRepository;
    }

    /**
     * Retire a retirable model.
     *
     * @param  string|null $retirementDate
     * @return void
     */
    public function retire(string $retirementDate = null)
    {
        throw_unless($this->retirable->canBeRetired(), new CannotBeRetiredException);

        $retirementDate = $retirementDate ?: now()->toDateTimeString();

        if ($this->retirable->isSuspended()) {
            $this->wrestlerRepository->reinstate($this->retirable, $retirementDate);
        }

        if ($this->retirable->isInjured()) {
            $this->wrestlerRepository->clearInjury($this->retirable, $retirementDate);
        }

        $this->wrestlerRepository->release($this->retirable, $retirementDate);
        $this->wrestlerRepository->retire($this->retirable, $retirementDate);
        $this->retirable->updateStatusAndSave();

        if ($this->retirable->currentTagTeam) {
            $this->retirable->currentTagTeam->updateStatusAndSave();
        }
    }
}
