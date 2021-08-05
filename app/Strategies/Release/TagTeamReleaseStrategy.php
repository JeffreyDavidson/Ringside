<?php

namespace App\Strategies\Release;

use App\Exceptions\CannotBeSuspendedException;
use App\Models\Contracts\Releasable;
use Carbon\Carbon;

class TagTeamReleaseStrategy extends BaseReleaseStrategy implements ReleaseStrategyInterface
{
    private Releasable $releasable;

    public function __construct(Releasable $releasable)
    {
        $this->releasable = $releasable;
    }

    public function release(Carbon $releasedAt = null)
    {
        throw_unless($this->releasable->canBeSuspended(), new CannotBeSuspendedException());
    }
}
