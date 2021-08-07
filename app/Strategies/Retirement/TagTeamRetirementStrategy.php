<?php

namespace App\Strategies\Retirement;

use App\Exceptions\CannotBeRetiredException;
use App\Models\Contracts\Retirable;
use App\Repositories\TagTeamRepository;

class TagTeamRetirementStrategy extends BaseRetirementStrategy implements RetirementStrategyInterface
{
    /**
     * The interface implementation.
     *
     * @var \App\Models\Contracts\Retirable
     */
    private Retirable $retirable;

    /**
     * The repository implementation.
     *
     * @var \App\Repositories\TagTeamRepository
     */
    private TagTeamRepository $tagTeamRepository;

    /**
     * Create a new tag team retirement strategy instance.
     *
     * @param \App\Models\Contracts\Retirable $retirable
     */
    public function __construct(Retirable $retirable)
    {
        $this->retirable = $retirable;
        $this->tagTeamRepository = new TagTeamRepository;
    }

    /**
     * Retire a retirable model.
     *
     * @param  string|null $retirementDate
     * @return void
     */
    public function retire(string $retirementDate = null)
    {
        throw_unless($this->retirable->canBeRetired(), new CannotBeRetiredException);

        $retirementDate = $retirementDate ?: now();

        if ($this->retirable->isSuspended()) {
            $this->tagTeamRepository->reinstate($this->retirable, $retirementDate);
        }

        $this->tagTeamRepository->release($this->retirable, $retirementDate);
        $this->tagTeamRepository->retire($this->retirable, $retirementDate);

        $this->retirable->currentWrestlers->each->retire($retirementDate);
        $this->retirable->updateStatusAndSave();
    }
}
