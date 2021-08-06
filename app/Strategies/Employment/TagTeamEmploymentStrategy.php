<?php

namespace App\Strategies\Employment;

use App\Exceptions\CannotBeEmployedException;
use App\Models\Contracts\Employable;
use Carbon\Carbon;

class TagTeamEmploymentStrategy extends BaseEmploymentStrategy implements EmploymentStrategyInterface
{
    /**
     * The interface implementation.
     *
     * @var \App\Models\Contracts\Employable
     */
    private Employable $employable;

    /**
     * Create a new tag team employment strategy instance.
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
     * @param  \Carbon\Carbon|null $startedAt
     * @return void
     */
    public function employ(Carbon $startedAt = null)
    {
        throw_unless($this->employable->canBeEmployed(), new CannotBeEmployedException);

        $startAtDate = Carbon::parse($startedAt)->toDateTimeString('minute') ?? now()->toDateTimeString('minute');

        $this->employable->employments()->updateOrCreate(['ended_at' => null], ['started_at' => $startAtDate]);

        if ($this->employable->currentWrestlers->every->isNotInEmployment()) {
            $this->employable->currentWrestlers->each->employ($startAtDate);
        }

        $this->employable->updateStatusAndSave();
    }
}
