<?php

namespace App\Strategies\Employment;

use App\Exceptions\CannotBeEmployedException;
use App\Models\Contracts\Employable;
use Carbon\Carbon;

class TagTeamEmploymentStrategy extends BaseEmploymentStrategy implements EmploymentStrategyInterface
{
    private Employable $employable;

    public function __construct(Employable $employable)
    {
        $this->employable = $employable;
    }

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
