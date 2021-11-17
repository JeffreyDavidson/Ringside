<?php

namespace App\Actions\TagTeams;

use App\Repositories\TagTeamRepository;
use App\Repositories\WrestlerRepository;

abstract class BaseTagTeamAction
{
    protected TagTeamRepository $tagTeamRepository;

    protected WrestlerRepository $wrestlerRepository;

    /**
     * Create a new base tag team action instance.
     *
     * @param  \App\Repositories\TagTeamRepository  $tagTeamRepository
     * @param  \App\Repositories\WrestlerRepository  $wrestlerRepository
     */
    public function __construct(TagTeamRepository $tagTeamRepository, WrestlerRepository $wrestlerRepository)
    {
        $this->tagTeamRepository = $tagTeamRepository;
        $this->wrestlerRepository = $wrestlerRepository;
    }
}
