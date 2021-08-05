<?php

namespace App\Strategies\Suspend;

use App\Exceptions\CannotBeSuspendedException;
use App\Models\Contracts\Suspendable;
use Carbon\Carbon;

class TagTeamSuspendStrategy extends BaseSuspendStrategy implements SuspendStrategyInterface
{
    private Suspendable $suspendable;

    public function __construct(Suspendable $suspendable)
    {
        $this->suspendable = $suspendable;
    }

    public function suspend(Carbon $suspendedAt = null)
    {
        throw_unless($this->suspendable->canBeSuspended(), new CannotBeSuspendedException);

        $suspendedDate = $suspendedAt ?: now();

        $this->suspendable->suspensions()->create(['started_at' => $suspendedDate]);
        $this->suspendable->currentWrestlers->each->suspend($suspendedDate);
        $this->suspendable->updateStatusAndSave();
    }
}
