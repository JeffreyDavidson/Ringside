<?php

namespace App\Strategies\Release;

use App\Exceptions\CannotBeReleasedException;
use App\Models\Contracts\Releasable;
use App\Repositories\RefereeRepository;
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
     * The repository implementation.
     *
     * @var \App\Repositories\RefereeRepository
     */
    private RefereeRepository $refereeRepository;

    /**
     * Create a new referee releasable strategy instance.
     *
     * @param \App\Models\Contracts\Releasable $releasable
     */
    public function __construct(Releasable $releasable)
    {
        $this->releasable = $releasable;
        $this->refereeRepository = new RefereeRepository;
    }

    /**
     * Release a releasable model.
     *
     * @param  string|null $releaseDate
     * @return void
     */
    public function release(string $releaseDate = null)
    {
        throw_unless($this->releasable->canBeReleased(), new CannotBeReleasedException);

        $releaseDate = $releasedAt ?? now()->toDateTimeString();

        if ($this->releasable->isSuspended()) {
            $this->refereeRepository->reinstate($this->releasable, $releaseDate);
        }

        if ($this->releasable->isInjured()) {
            $this->refereeRepository->clearInjury($this->releasable, $releaseDate);
        }

        $this->refereeRepository->release($this->releasable, $releaseDate);
        $this->releasable->updateStatusAndSave();
    }
}
