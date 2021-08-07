<?php

namespace App\Strategies\Retirement;

use App\Exceptions\CannotBeRetiredException;
use App\Models\Contracts\Retirable;
use App\Repositories\RefereeRepository;

class RefereeRetirementStrategy extends BaseRetirementStrategy implements RetirementStrategyInterface
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
     * @var \App\Repositories\RefereeRepository
     */
    private RefereeRepository $refereeRepository;

    /**
     * Create a new referee retirement strategy instance.
     *
     * @param \App\Models\Contracts\Retirable $retirable
     */
    public function __construct(Retirable $retirable)
    {
        $this->retirable = $retirable;
        $this->refereeRepository = new RefereeRepository;
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
            $this->refereeRepository->reinstate($this->retirable, $retirementDate);
        }

        if ($this->retirable->isInjured()) {
            $this->refereeRepository->clearInjury($this->retirable, $retirementDate);
        }

        $this->refereeRepository->release($this->retirable, $retirementDate);
        $this->refereeRepository->retire($this->retirable, $retirementDate);
        $this->retirable->updateStatusAndSave();
    }
}
