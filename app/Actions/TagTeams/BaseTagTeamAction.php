<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Repositories\TagTeamRepository;
use App\Repositories\WrestlerRepository;

abstract class BaseTagTeamAction
{
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
