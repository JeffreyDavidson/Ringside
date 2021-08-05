<?php

namespace App\Strategies\Suspend;

use App\Exceptions\CannotBeSuspendedException;
use App\Models\Contracts\Suspendable;
use Carbon\Carbon;

class WrestlerSuspendStrategy extends BaseSuspendStrategy implements SuspendStrategyInterface
{
    private Suspendable $suspendable;

    public function __construct(Suspendable $suspendable)
    {
        $this->suspendable = $suspendable;
    }

    public function suspend(Carbon $suspendedAt = null)
    {
        throw_unless($this->suspendable->canBeSuspended(), new CannotBeSuspendedException);

        $suspensionDate = Carbon::parse($suspendedAt)->toDateTimeString() ?? now()->toDateTimeString();

        $this->suspendable->suspensions()->create(['started_at' => $suspensionDate]);
        $this->suspendable->updateStatusAndSave();

        if ($this->suspendable->currentTagTeam) {
            $this->suspendable->currentTagTeam->updateStatusAndSave();
        }
    }
}
