<?php

namespace App\Strategies\Release;

use App\Exceptions\CannotBeReleasedException;
use App\Models\Contracts\Releasable;
use App\Strategies\ClearInjury\RefereeClearInjuryStrategy;
use App\Strategies\Reinstate\RefereeReinstateStrategy;

class RefereeReleaseStrategy extends BaseReleaseStrategy implements ReleaseStrategyInterface
{
    /**
     * The interface implementation.
     *
     * @var \App\Models\Contracts\Releasable
     */
    private Releasable $releasable;

    /**
     * Create a new referee releasable strategy instance.
     *
     * @param \App\Models\Contracts\Releasable $releasable
     */
    public function __construct(Releasable $releasable)
    {
        $this->releasable = $releasable;
    }

    /**
     * Release a releasable model.
     *
     * @param  string|null $releasedAt
     * @return void
     */
    public function release(string $releasedAt = null)
    {
        throw_unless($this->releasable->canBeReleased(), new CannotBeReleasedException);

        if ($this->releasable->isSuspended()) {
            (new RefereeReinstateStrategy($this->releasable))->reinstate();
        }

        if ($this->releasable->isInjured()) {
            (new RefereeClearInjuryStrategy($this->releasable))->clearInjury();
        }

        $releaseDate = $releasedAt ?? now()->toDateTimeString();

        $this->releasable->currentEmployment->update(['ended_at' => $releaseDate]);
        $this->releasable->updateStatusAndSave();
    }
}
