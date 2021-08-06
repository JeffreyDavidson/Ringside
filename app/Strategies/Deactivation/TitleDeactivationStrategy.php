<?php

namespace App\Strategies\Deactivation;

use App\Models\Contracts\Deactivatable;
use App\Repositories\DeactivationRepositoryInterface;
use App\Repositories\TitleRepository;

class TitleDeactivationStrategy extends BaseDeactivationStrategy
{
    /**
     * Create a new title deactivation strategy instance.
     *
     * @param \App\Models\Contracts\Deactivatable $deactivatable
     * @param \App\Repositories\DeactivationRepositoryInterface|null $repository
     */
    public function __construct(Deactivatable $deactivatable, DeactivationRepositoryInterface $repository = null)
    {
        parent::__construct($deactivatable, $repository ?? new TitleRepository());
    }
}
