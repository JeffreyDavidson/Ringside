<?php

namespace App\Strategies\Deactivation;

use App\Models\Contracts\Deactivatable;
use App\Repositories\Contracts\DeactivationRepositoryInterface;
use App\Repositories\StableRepository;

class StableDeactivationStrategy extends BaseDeactivationStrategy
{
    /**
     * Create a new stable deactivation strategy instance.
     *
     * @param \App\Models\Contracts\Deactivatable $deactivatable
     * @param \App\Repositories\Contracts\DeactivationRepositoryInterface|null $repository
     */
    public function __construct(Deactivatable $deactivatable, DeactivationRepositoryInterface $repository = null)
    {
        parent::__construct($deactivatable, $repository ?? new StableRepository());
    }
}
