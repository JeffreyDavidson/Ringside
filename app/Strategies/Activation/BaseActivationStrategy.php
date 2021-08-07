<?php

namespace App\Strategies\Activation;

use App\Exceptions\CannotBeActivatedException;
use App\Models\Contracts\Activatable;
use App\Repositories\Contracts\ActivationRepositoryInterface;

class BaseActivationStrategy implements ActivationStrategyInterface
{
    /**
     * The interface implementation.
     *
     * @var \App\Models\Contracts\Activatable
     */
    private Activatable $activatable;

    /**
     * The repository associated with the interface.
     *
     * @var \App\Repositories\Contracts\ActivationRepositoryInterface
     */
    private ActivationRepositoryInterface $repository;

    /**
     * Create a new base activation strategy instance.
     *
     * @param \App\Models\Contracts\Activatable $activatable
     * @param \App\Repositories\Contracts\ActivationRepositoryInterface $repository
     */
    public function __construct(Activatable $activatable, ActivationRepositoryInterface $repository)
    {
        $this->activatable = $activatable;
        $this->repository = $repository;
    }

    /**
     * Activate an activatable model.
     *
     * @param  string|null $activatedAtDate
     * @return void
     */
    public function activate(string $activatedAtDate = null)
    {
        throw_unless($this->activatable->canBeActivated(), new CannotBeActivatedException);

        $activatedAtDate = $activatedAtDate ?? now()->toDateTimeString();

        $this->repository->activate($this->activatable, $activatedAtDate);
        $this->activatable->updateStatusAndSave();
    }
}
