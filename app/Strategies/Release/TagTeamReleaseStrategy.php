<?php

namespace App\Strategies\Release;

use App\Exceptions\CannotBeSuspendedException;
use App\Models\Contracts\Releasable;
use App\Repositories\TagTeamRepository;

class TagTeamReleaseStrategy extends BaseReleaseStrategy implements ReleaseStrategyInterface
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
     * @var \App\Repositories\TagTeamRepository
     */
    private TagTeamRepository $tagTeamRepository;

    /**
     * Create a new tag team releasable strategy instance.
     *
     * @param \App\Models\Contracts\Releasable $releasable
     */
    public function __construct(Releasable $releasable)
    {
        $this->releasable = $releasable;
        $this->tagTeamRepository = new TagTeamRepository;
    }

    /**
     * Release a releasable model.
     *
     * @param  string|null $releasedAt
     * @return void
     */
    public function release(string $releasedAt = null)
    {
        throw_unless($this->releasable->canBeSuspended(), new CannotBeSuspendedException());
    }
}
