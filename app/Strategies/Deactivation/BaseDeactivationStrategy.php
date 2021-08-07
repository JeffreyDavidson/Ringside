<?php

namespace App\Strategies\Deactivation;

use App\Exceptions\CannotBeDeactivatedException;
use App\Models\Contracts\Deactivatable;
use App\Repositories\Contracts\DeactivationRepositoryInterface;

class BaseDeactivationStrategy implements DeactivationStrategyInterface
{
    /**
     * The interface implementation.
     *
     * @var \App\Models\Contracts\Deactivatable
     */
    private Deactivatable $deactivatable;

    /**
     * The repository associated with the interface.
     *
     * @var \App\Repositories\Contracts\DeactivationRepositoryInterface
     */
    private DeactivationRepositoryInterface $repository;

    /**
     * Create a new base deactivatable service instance.
     *
     * @param \App\Models\Contracts\Deactivatable $deactivatable
     * @param \App\Repositories\Contracts\DeactivationRepositoryInterface $repository
     */
    public function __construct(Deactivatable $deactivatable, DeactivationRepositoryInterface $repository)
    {
        $this->deactivatable = $deactivatable;
        $this->repository = $repository;
    }

    /**
     * Deactivate a deactivatable model.
     *
     * @param  string|null $deactivationDate
     * @return void
     */
    public function deactivate(string $deactivationDate = null)
    {
        throw_unless($this->deactivatable->canBeDeactivated(), new CannotBeDeactivatedException);

        $deactivationDate = $deactivationDate ?? now()->toDateTimeString();

        $this->repository->deactivate($this->deactivatable, $deactivationDate);
        $this->deactivatable->updateStatusAndSave();
    }
}
