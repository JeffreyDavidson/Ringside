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
     * @param  string|null $startedAt
     * @return void
     */
    public function activate(string $startedAt = null)
    {
        throw_unless($this->activatable->canBeActivated(), new CannotBeActivatedException);

        $this->repository->activate($this->activatable, $startedAt);
        $this->activatable->updateStatusAndSave();
    }
}
