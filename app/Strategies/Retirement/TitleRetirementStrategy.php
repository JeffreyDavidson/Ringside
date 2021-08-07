<?php

namespace App\Strategies\Retirement;

use App\Exceptions\CannotBeRetiredException;
use App\Models\Contracts\Retirable;
use App\Repositories\TitleRepository;

class TitleRetirementStrategy extends BaseRetirementStrategy implements RetirementStrategyInterface
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
     * @var \App\Repositories\TitleRepository
     */
    private TitleRepository $titleRepository;

    /**
     * Create a new title retirement strategy instance.
     *
     * @param \App\Models\Contracts\Retirable $retirable
     */
    public function __construct(Retirable $retirable)
    {
        $this->retirable = $retirable;
        $this->titleRepository = new TitleRepository;
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

        $this->titleRepository->deactivate($this->retirable, $retirementDate);
        $this->titleRepository->retire($this->retirable, $retirementDate);
        $this->retirable->updateStatusAndSave();
    }
}
