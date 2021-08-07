<?php

namespace App\Strategies\Retirement;

use App\Exceptions\CannotBeRetiredException;
use App\Models\Contracts\Retirable;
use App\Repositories\StableRepository;

class StableRetirementStrategy extends BaseRetirementStrategy implements RetirementStrategyInterface
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
     * @var \App\Repositories\StableRepository
     */
    private StableRepository $stableRepository;

    /**
     * Create a new stable retirement strategy instance.
     *
     * @param \App\Models\Contracts\Retirable $retirable
     */
    public function __construct(Retirable $retirable)
    {
        $this->retirable = $retirable;
        $this->stableRepository = new StableRepository;
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

        $this->stableRepository->deactivate($this->retirable, $retirementDate);
        $this->stableRepository->retire($this->retirable, $retirementDate);

        if ($this->retirable->currentWrestlers->every->isNotInEmployment()) {
            foreach ($this->retirable->currentWrestlers as $wrestler) {
                (new WrestlerRetirementStrategy($wrestler))->retire($this->retirable->currentEmployment->started_at);
            }
        }

        if ($this->retirable->currentTagTeams->every->isNotInEmployment()) {
            foreach ($this->retirable->currentTagTeams as $tagTeam) {
                (new TagTeamRetirementStrategy($tagTeam))->retire($this->retirable->currentEmployment->started_at);
            }
        }

        $this->retirable->updateStatusAndSave();
    }
}
