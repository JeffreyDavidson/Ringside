<?php

namespace App\Strategies\Retirement;

use App\Exceptions\CannotBeRetiredException;
use App\Models\Contracts\Retirable;
use App\Strategies\Reinstate\TagTeamReinstateStrategy;

class TagTeamRetirementStrategy extends BaseRetirementStrategy implements RetirementStrategyInterface
{
    /**
     * The interface implementation.
     *
     * @var \App\Models\Contracts\Retirable
     */
    private Retirable $retirable;

    /**
     * Create a new tag team retirement strategy instance.
     *
     * @param \App\Models\Contracts\Retirable $retirable
     */
    public function __construct(Retirable $retirable)
    {
        $this->retirable = $retirable;
    }

    /**
     * Retire a retirable model.
     *
     * @param  string|null $retiredAt
     * @return void
     */
    public function retire(string $retiredAt = null)
    {
        throw_unless($this->retirable->canBeRetired(), new CannotBeRetiredException);

        $retiredDate = $retiredAt ?: now();

        if ($this->retirable->isSuspended()) {
            (new TagTeamReinstateStrategy($this->retirable))->reinstate();
        }

        $this->repository->release($this->retirable, $retiredDate);
        $this->repository->retire($this->retirable, $retiredDate);

        $this->retirable->currentWrestlers->each->retire($retiredDate);
        $this->retirable->updateStatusAndSave();
    }
}
