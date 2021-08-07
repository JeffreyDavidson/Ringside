<?php

namespace App\Strategies\Release;

use App\Exceptions\CannotBeReleasedException;
use App\Models\Contracts\Releasable;
use App\Repositories\WrestlerRepository;
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
     * The repository implementation.
     *
     * @var \App\Repositories\WrestlerRepository
     */
    private WrestlerRepository $wrestlerRepository;

    /**
     * Create a new wrestler releasable strategy instance.
     *
     * @param \App\Models\Contracts\Releasable $releasable
     */
    public function __construct(Releasable $releasable)
    {
        $this->releasable = $releasable;
        $this->wrestlerRepository = new WrestlerRepository;
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

        $releaseDate = $releaseDate ?? now()->toDateTimeString();

        if ($this->releasable->isSuspended()) {
            $this->wrestlerRepository->reinstate($this->releasable, $releaseDate);
        }

        if ($this->releasable->isInjured()) {
            $this->wrestlerRepository->clearInjury($this->releasable, $releaseDate);
        }

        $this->wrestlerRepository->release($this->releasable, $releaseDate);
        $this->releasable->updateStatusAndSave();

        if ($this->releasable->currentTagTeam) {
            $this->releasable->currentTagTeam->updateStatusAndSave();
        }
    }
}
