<?php

namespace App\Strategies\Activation;

use App\Models\Contracts\Activatable;
use App\Repositories\Contracts\ActivationRepositoryInterface;
use App\Repositories\StableRepository;

class StableActivationStrategy extends BaseActivationStrategy
{
    /**
     * Create a new stable activation strategy instance.
     *
     * @param \App\Models\Contracts\Activatable $activatable
     * @param \App\Repositories\Contracts\ActivationRepositoryInterface|null $repository
     */
    public function __construct(Activatable $activatable, ActivationRepositoryInterface $repository = null)
    {
        parent::__construct($activatable, $repository ?? new StableRepository());
    }
}
