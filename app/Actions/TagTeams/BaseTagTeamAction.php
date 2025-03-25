<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Repositories\TagTeamRepository;
use App\Repositories\WrestlerRepository;

abstract class BaseTagTeamAction
{
    /**
     * Create a new base tag team action instance.
     */
    public function __construct(
        protected TagTeamRepository $tagTeamRepository,
        protected WrestlerRepository $wrestlerRepository
    ) {}
}
