<?php

namespace App\Strategies\Suspend;

use App\Exceptions\CannotBeSuspendedException;
use App\Models\Contracts\Suspendable;
use Carbon\Carbon;

class WrestlerSuspendStrategy extends BaseSuspendStrategy implements SuspendStrategyInterface
{
    /**
     * The interface implementation.
     *
     * @var \App\Models\Contracts\Suspendable
     */
    private Suspendable $suspendable;

    /**
     * Create a new wrestler suspend strategy instance.
     *
     * @param \App\Models\Contracts\Suspendable $suspendable
     */
    public function __construct(Suspendable $suspendable)
    {
        $this->suspendable = $suspendable;
    }

    /**
     * Suspend a suspendable model.
     *
     * @param  \Carbon\Carbon|null $suspendedAt
     * @return void
     */
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
