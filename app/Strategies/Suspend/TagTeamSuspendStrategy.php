<?php

namespace App\Strategies\Suspend;

use App\Exceptions\CannotBeSuspendedException;
use App\Models\Contracts\Suspendable;

class TagTeamSuspendStrategy extends BaseSuspendStrategy implements SuspendStrategyInterface
{
    /**
     * The interface implementation.
     *
     * @var \App\Models\Contracts\Suspendable
     */
    private Suspendable $suspendable;

    /**
     * Create a new tag team suspend strategy instance.
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

        $suspendedDate = $suspendedAt ?: now();

        $this->suspendable->suspensions()->create(['started_at' => $suspendedDate]);
        $this->suspendable->currentWrestlers->each->suspend($suspendedDate);
        $this->suspendable->updateStatusAndSave();
    }
}
