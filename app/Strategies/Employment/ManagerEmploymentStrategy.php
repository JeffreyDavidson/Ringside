<?php

namespace App\Strategies\Employment;

use App\Exceptions\CannotBeEmployedException;
use App\Models\Contracts\Employable;

class ManagerEmploymentStrategy extends BaseEmploymentStrategy implements EmploymentStrategyInterface
{
    /**
     * The interface implementation.
     *
     * @var \App\Models\Contracts\Employable
     */
    private Employable $employable;

    /**
     * Create a new manager employment strategy instance.
     *
     * @param \App\Models\Contracts\Employable $employable
     */
    public function __construct(Employable $employable)
    {
        $this->employable = $employable;
    }

    /**
     * Employ an employable model.
     *
     * @param  string|null $startedAt
     * @return void
     */
    public function employ(string $startedAt = null)
    {
        throw_unless($this->employable->canBeEmployed(), new CannotBeEmployedException);

        $startAtDate = $startedAt ?? now()->toDateTimeString();

        $this->repository->employ($this->employable, $startAtDate);
        $this->employable->updateStatusAndSave();
    }
}
