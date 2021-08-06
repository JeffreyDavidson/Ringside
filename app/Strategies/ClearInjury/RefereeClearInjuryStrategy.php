<?php

namespace App\Strategies\ClearInjury;

use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Models\Contracts\Injurable;
use App\Repositories\RefereeRepository;

class RefereeClearInjuryStrategy extends BaseClearInjuryStrategy implements ClearInjuryStrategyInterface
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
     * @var \App\Repositories\RefereeRepository
     */
    private RefereeRepository $refereeRepository;

    /**
     * Create a new referee clear injury strategy instance.
     *
     * @param \App\Models\Contracts\Injurable $injurable
     * @param \App\Repositories\RefereeRepository $refereeRepository
     */
    public function __construct(Injurable $injurable, RefereeRepository $refereeRepository)
    {
        $this->injurable = $injurable;
        $this->refereeRepository = $refereeRepository;
    }

    /**
     * Clear an injury of an injurable model.
     *
     * @param  string|null $recoveredAt
     * @return void
     */
    public function clearInjury(string $recoveredAt = null)
    {
        throw_unless($this->injurable->canBeClearedFromInjury(), new CannotBeClearedFromInjuryException);

        $recoveryDate = $recoveredAt ?? now()->toDateTimeString();

        $this->refereeRepository->clearInjury($this->injurable, $recoveryDate);
        $this->refereeRepository->updateStatusAndSave();
    }
}
