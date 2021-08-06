<?php

namespace App\Strategies\Employment;

use App\Exceptions\CannotBeEmployedException;
use App\Models\Contracts\Employable;

class RefereeEmploymentStrategy extends BaseEmploymentStrategy implements EmploymentStrategyInterface
{
    /**
     * The interface implementation.
     *
     * @var \App\Models\Contracts\Employable
     */
    private Employable $employable;

    /**
     * Create a new referee employment strategy instance.
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
    public function employ($startedAt = null)
    {
        throw_unless($this->employable->canBeEmployed(), new CannotBeEmployedException);

        $startDate = $startedAt ?? now()->toDateTimeString();

        $this->employable->employments()->updateOrCreate(['ended_at' => null], ['started_at' => $startDate]);
        $this->employable->updateStatusAndSave();
    }
}
