<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Repositories\StableRepository;
use App\Repositories\TagTeamRepository;
use App\Repositories\WrestlerRepository;

abstract class BaseStableAction
{
    /**
     * Create a new base stable action instance.
     */
    public function __construct(
        protected StableRepository $stableRepository,
        protected TagTeamRepository $tagTeamRepository,
        protected WrestlerRepository $wrestlerRepository
    ) {
    }
}
