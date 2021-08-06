<?php

namespace App\Strategies\Deactivation;

use App\Exceptions\CannotBeDeactivatedException;
use App\Models\Contracts\Deactivatable;
use App\Repositories\DeactivationRepositoryInterface;
use Carbon\Carbon;

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
     * @var \App\Repositories\DeactivationRepositoryInterface
     */
    private DeactivationRepositoryInterface $repository;

    /**
     * Create a new base deactivatable service instance.
     *
     * @param \App\Models\Contracts\Deactivatable $deactivatable
     * @param \App\Repositories\DeactivationRepositoryInterface $repository
     */
    public function __construct(Deactivatable $deactivatable, DeactivationRepositoryInterface $repository)
    {
        $this->deactivatable = $deactivatable;
        $this->repository = $repository;
    }

    /**
     * Deactivate a deactivatable model.
     *
     * @param  \Carbon\Carbon|null $startedAt
     * @return void
     */
    public function deactivate(Carbon $startedAt = null)
    {
        throw_unless($this->canBeDeactivated(), new CannotBeDeactivatedException);

        $this->repository->deactivate($this->deactivatable, $startedAt);
        $this->deactivatable->updateStatusAndSave();
    }

    /**
     * Determine if the deactivatable can be deactivated.
     *
     * @return bool
     */
    public function canBeDeactivated()
    {
        if ($this->isNotInActivation()) {
            return false;
        }

        return true;
    }

    /**
     * Check to see if the model is not in activation.
     *
     * @return bool
     */
    public function isNotInActivation()
    {
        return $this->isNotActivated() || $this->isDeactivated() || $this->hasFutureActivation() || $this->isRetired();
    }
}
