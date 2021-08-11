<?php

namespace App\Strategies\Employment;

use App\Exceptions\CannotBeEmployedException;
use App\Models\Contracts\Employable;
use App\Repositories\RefereeRepository;

class RefereeEmploymentStrategy extends BaseEmploymentStrategy implements EmploymentStrategyInterface
{
    /**
     * The interface implementation.
     *
     * @var \App\Models\Contracts\Employable
     */
    private Employable $employable;

    /**
     * The repository implementation.
     *
     * @var \App\Repositories\RefereeRepository
     */
    private RefereeRepository $refereeRepository;

    /**
     * Create a new referee employment strategy instance.
     *
     * @param \App\Models\Contracts\Employable $employable
     */
    public function __construct(Employable $employable)
    {
        $this->employable = $employable;
        $this->refereeRepository = new RefereeRepository;
    }

    /**
     * Employ an employable model.
     *
     * @param  string|null $employmentDate
     * @return void
     */
    public function employ(string $employmentDate = null)
    {
        throw_unless($this->employable->canBeEmployed(), new CannotBeEmployedException);

        $employmentDate ??= now()->toDateTimeString();

        $this->refereeRepository->employ($this->employable, $employmentDate);
        $this->employable->updateStatusAndSave();
    }
}
