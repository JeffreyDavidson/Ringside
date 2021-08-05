<?php

namespace App\Strategies\Employment;

use App\Exceptions\CannotBeEmployedException;
use App\Models\Contracts\Employable;
use Carbon\Carbon;

class RefereeEmploymentStrategy extends BaseEmploymentStrategy implements EmploymentStrategyInterface
{
    private Employable $employable;

    public function __construct(Employable $employable)
    {
        $this->employable = $employable;
    }

    public function employ(Carbon $startedAt = null)
    {
        throw_unless($this->employable->canBeEmployed(), new CannotBeEmployedException);

        $startDate = Carbon::parse($startedAt)->toDateTimeString() ?? now()->toDateTimeString();

        $this->employable->employments()->updateOrCreate(['ended_at' => null], ['started_at' => $startDate]);
        $this->employable->updateStatusAndSave();
    }
}
