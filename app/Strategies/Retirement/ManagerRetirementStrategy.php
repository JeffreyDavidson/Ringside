<?php

namespace App\Strategies\Retirement;

use App\Exceptions\CannotBeRetiredException;
use App\Models\Contracts\Retirable;
use App\Strategies\ClearInjury\ManagerClearInjuryStrategy;
use App\Strategies\Reinstate\ManagerReinstateStrategy;

class ManagerRetirementStrategy extends BaseRetirementStrategy implements RetirementStrategyInterface
{
    /**
     * The interface implementation.
     *
     * @var \App\Models\Contracts\Retirable
     */
    private Retirable $retirable;

    /**
     * Create a new manager retirement strategy instance.
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

        $retiredDate = $retiredAt ?: now()->toDateTimeString();

        if ($this->retirable->isSuspended()) {
            $this->repository->reinstate($this->retirable, $retiredDate);
        }

        if ($this->retirable->isInjured()) {
            $this->repository->clearInjury($this->retirable, $retiredDate);
        }

        $this->repository->release($this->retirable, $retiredDate);
        $this->repository->retire($this->retirable, $retiredDate);
        $this->retirable->updateStatusAndSave();
    }
}
