<?php

namespace App\Strategies\Retirement;

use App\Exceptions\CannotBeRetiredException;
use App\Models\Contracts\Retirable;
use App\Repositories\ManagerRepository;

class ManagerRetirementStrategy extends BaseRetirementStrategy implements RetirementStrategyInterface
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
     * @var \App\Repositories\ManagerRepository
     */
    private ManagerRepository $managerRepository;

    /**
     * Create a new manager retirement strategy instance.
     *
     * @param \App\Models\Contracts\Retirable $retirable
     */
    public function __construct(Retirable $retirable)
    {
        $this->retirable = $retirable;
        $this->managerRepository = new ManagerRepository;
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
            $this->managerRepository->reinstate($this->retirable, $retirementDate);
        }

        if ($this->retirable->isInjured()) {
            $this->managerRepository->clearInjury($this->retirable, $retirementDate);
        }

        $this->managerRepository->release($this->retirable, $retirementDate);
        $this->managerRepository->retire($this->retirable, $retirementDate);
        $this->retirable->updateStatusAndSave();
    }
}
