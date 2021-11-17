<?php

namespace App\Actions\Stables;

use App\Repositories\StableRepository;
use App\Repositories\TagTeamRepository;
use App\Repositories\WrestlerRepository;

abstract class BaseStableAction
{
    protected StableRepository $stableRepository;

    protected TagTeamRepository $tagTeamRepository;

    protected WrestlerRepository $wrestlerRepository;

    /**
     * Create a new stable action instance.
     *
     * @param  \App\Repositories\StableRepository  $stableRepository
     * @param  \App\Repositories\TagTeamRepository  $tagTeamRepository
     * @param  \App\Repositories\WrestlerRepository  $wrestlerRepository
     */
    public function __construct(
        StableRepository $stableRepository,
        TagTeamRepository $tagTeamRepository,
        WrestlerRepository $wrestlerRepository
    ) {
        $this->stableRepository = $stableRepository;
        $this->tagTeamRepository = $tagTeamRepository;
        $this->wrestlerRepository = $wrestlerRepository;
    }
}
