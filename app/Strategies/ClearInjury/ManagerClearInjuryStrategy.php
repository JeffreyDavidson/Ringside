<?php

namespace App\Strategies\ClearInjury;

use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Models\Contracts\Injurable;
use App\Repositories\ManagerRepository;

class ManagerClearInjuryStrategy extends BaseClearInjuryStrategy implements ClearInjuryStrategyInterface
{
    /**
     * The interface implementation.
     *
     * @var \App\Models\Contracts\Injurable
     */
    private Injurable $injurable;

    /**
     * The repository implementation.
     *
     * @var \App\Repositories\ManagerRepository
     */
    private ManagerRepository $managerRepository;

    /**
     * Create a new manager clear injury strategy instance.
     *
     * @param \App\Models\Contracts\Injurable $injurable
     * @param \App\Repositories\ManagerRepository $managerRepository
     */
    public function __construct(Injurable $injurable, ManagerRepository $managerRepository)
    {
        $this->injurable = $injurable;
        $this->managerRepository = $managerRepository;
    }

    /**
     * Clear an injury of an injurable model.
     *
     * @param  string|null $recoveredAt
     * @return void
     */
    public function clearInjury($recoveredAt = null)
    {
        throw_unless($this->injurable->canBeClearedFromInjury(), new CannotBeClearedFromInjuryException);

        $recoveryDate = $recoveredAt ?? now()->toDateTimeString();

        $this->managerRepository->clearInjury($this->injurable, $recoveryDate);
        $this->injurable->updateStatusAndSave();
    }
}
