<?php

namespace App\Strategies\Release;

use App\Exceptions\CannotBeReleasedException;
use App\Models\Contracts\Releasable;
use App\Strategies\ClearInjury\ManagerClearInjuryStrategy;
use App\Strategies\Reinstate\ManagerReinstateStrategy;
use Carbon\Carbon;

class ManagerReleaseStrategy extends BaseReleaseStrategy implements ReleaseStrategyInterface
{
    /**
     * The interface implementation.
     *
     * @var \App\Models\Contracts\Releasable
     */
    private Releasable $releasable;

    /**
     * Create a new manager releasable strategy instance.
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
     * @param  \Carbon\Carbon|null $releasedAt
     * @return void
     */
    public function release(Carbon $releasedAt = null)
    {
        throw_unless($this->releasable->canBeReleased(), new CannotBeReleasedException);

        $releaseDate = Carbon::parse($releasedAt)->toDateTimeString() ?? now()->toDateTimeString();

        if ($this->releasable->isSuspended()) {
            $this->repository->reinstate($this->releasable, $releaseDate);
        }

        if ($this->releasable->isInjured()) {
            $this->repository->clearInjury($this->releasable, $releaseDate);
        }

        $this->repository->release($this->releasable, $releaseDate);
        $this->releasable->updateStatusAndSave();

        if ($this->releasable->currentTagTeam) {
            $this->releasable->currentTagTeam->updateStatusAndSave();
        }
    }
}
