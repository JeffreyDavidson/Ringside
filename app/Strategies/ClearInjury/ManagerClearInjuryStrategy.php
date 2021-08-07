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
     */
    public function __construct(Injurable $injurable)
    {
        $this->injurable = $injurable;
        $this->managerRepository = new ManagerRepository;
    }

    /**
     * Clear an injury of an injurable model.
     *
     * @param  string|null $recoveryDate
     * @return void
     */
    public function clearInjury(string $recoveryDate = null)
    {
        throw_unless($this->injurable->canBeClearedFromInjury(), new CannotBeClearedFromInjuryException);

        $recoveryDate = $recoveryDate ?? now()->toDateTimeString();

        $this->managerRepository->clearInjury($this->injurable, $recoveryDate);
        $this->injurable->updateStatusAndSave();
    }
}
