<?php

namespace App\Strategies\Suspend;

use App\Exceptions\CannotBeSuspendedException;
use App\Models\Contracts\Suspendable;
use App\Repositories\TagTeamRepository;

class TagTeamSuspendStrategy extends BaseSuspendStrategy implements SuspendStrategyInterface
{
    /**
     * The interface implementation.
     *
     * @var \App\Models\Contracts\Suspendable
     */
    private Suspendable $suspendable;

    /**
     * The repository implementation.
     *
     * @var \App\Repositories\TagTeamRepository
     */
    private TagTeamRepository $tagTeamRepository;

    /**
     * Create a new tag team suspend strategy instance.
     *
     * @param \App\Models\Contracts\Suspendable $suspendable
     */
    public function __construct(Suspendable $suspendable)
    {
        $this->suspendable = $suspendable;
        $this->tagTeamRepository = new TagTeamRepository;
    }

    /**
     * Suspend a suspendable model.
     *
     * @param  string|null $suspensionDate
     * @return void
     */
    public function suspend(string $suspensionDate = null)
    {
        throw_unless($this->suspendable->canBeSuspended(), new CannotBeSuspendedException);

        $suspensionDate = $suspensionDate ?: now();

        $this->tagTeamRepository->suspend($this->suspendable, $suspensionDate);

        $this->suspendable->currentWrestlers->each->suspend($suspensionDate);
        $this->suspendable->updateStatusAndSave();
    }
}
