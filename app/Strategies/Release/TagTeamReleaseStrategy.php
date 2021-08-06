<?php

namespace App\Strategies\Release;

use App\Exceptions\CannotBeSuspendedException;
use App\Models\Contracts\Releasable;
use Carbon\Carbon;

class TagTeamReleaseStrategy extends BaseReleaseStrategy implements ReleaseStrategyInterface
{
    /**
     * The interface implementation.
     *
     * @var \App\Models\Contracts\Releasable
     */
    private Releasable $releasable;

    /**
     * Create a new tag team releasable strategy instance.
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
        throw_unless($this->releasable->canBeSuspended(), new CannotBeSuspendedException());
    }
}
