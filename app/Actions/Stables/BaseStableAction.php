<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Repositories\StableRepository;
use App\Repositories\TagTeamRepository;
use App\Repositories\WrestlerRepository;

abstract class BaseStableAction
{
    /**
     * The repository to be used for stables.
     *
     * @var \App\Repositories\StableRepository
     */
    protected StableRepository $stableRepository;

    /**
     * The repository to be used for tag teams.
     *
     * @var \App\Repositories\TagTeamRepository
     */
    protected TagTeamRepository $tagTeamRepository;

    /**
     * The repository to be used for wrestlers.
     *
     * @var \App\Repositories\WrestlerRepository
     */
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
