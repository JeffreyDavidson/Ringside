<?php

namespace App\Strategies\Suspend;

use App\Exceptions\CannotBeSuspendedException;
use App\Models\Contracts\Suspendable;

class ManagerSuspendStrategy extends BaseSuspendStrategy implements SuspendStrategyInterface
{
    /**
     * The interface implementation.
     *
     * @var \App\Models\Contracts\Suspendable
     */
    private Suspendable $suspendable;

    /**
     * Create a new manager suspend strategy instance.
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
     * @param  string|null $suspendedAt
     * @return void
     */
    public function suspend(string $suspendedAt = null)
    {
        throw_unless($this->suspendable->canBeSuspended(), new CannotBeSuspendedException);

        $suspensionDate = $suspendedAt ?? now()->toDateTimeString();

        $this->suspendable->suspensions()->create(['started_at' => $suspensionDate]);
        $this->suspendable->updateStatusAndSave();

        if ($this->suspendable->currentTagTeam) {
            $this->suspendable->currentTagTeam->updateStatusAndSave();
        }
    }
}
