<?php

namespace App\Strategies\Injure;

use App\Exceptions\CannotBeInjuredException;
use App\Models\Contracts\Injurable;
use App\Repositories\RefereeRepository;

class RefereeInjuryStrategy extends BaseInjuryStrategy implements InjuryStrategyInterface
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
     * Create a new referee injury strategy instance.
     *
     * @param \App\Models\Contracts\Injurable $injurable
     */
    public function __construct(Injurable $injurable)
    {
        $this->injurable = $injurable;
        $this->refereeRepository = new RefereeRepository;
    }

    /**
     * Injure an injurable model.
     *
     * @param  string|null $injureDate
     * @return void
     */
    public function injure(string $injureDate = null)
    {
        throw_unless($this->injurable->canBeInjured(), new CannotBeInjuredException);

        $injureDate = $injureDate ?? now()->toDateTimeString();

        $this->refereeRepository->injure($this->injurable, $injureDate);
        $this->injurable->updateStatusAndSave();
    }
}
