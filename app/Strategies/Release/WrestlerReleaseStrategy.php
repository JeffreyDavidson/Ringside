<?php

namespace App\Strategies\Release;

use App\Exceptions\CannotBeReleasedException;
use App\Models\Contracts\Releasable;
use App\Strategies\ClearInjury\WrestlerClearInjuryStrategy;
use App\Strategies\Reinstate\WrestlerReinstateStrategy;

class WrestlerReleaseStrategy extends BaseReleaseStrategy implements ReleaseStrategyInterface
{
    /**
     * The interface implementation.
     *
     * @var \App\Models\Contracts\Releasable
     */
    private Releasable $releasable;

    /**
     * Create a new wrestler releasable strategy instance.
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
            (new WrestlerReinstateStrategy($this->releasable))->reinstate();
        }

        if ($this->releasable->isInjured()) {
            (new WrestlerClearInjuryStrategy($this->releasable))->clearInjury();
        }

        $releaseDate = $releasedAt ?? now()->toDateTimeString();

        $this->repository->release($this->releasable, $releaseDate);
        $this->releasable->updateStatusAndSave();

        if ($this->releasable->currentTagTeam) {
            $this->releasable->currentTagTeam->updateStatusAndSave();
        }
    }
}
