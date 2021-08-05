<?php

namespace App\Strategies\Release;

use App\Exceptions\CannotBeReleasedException;
use App\Models\Contracts\Releasable;
use App\Strategies\ClearInjury\RefereeClearInjuryStrategy;
use App\Strategies\Reinstate\RefereeReinstateStrategy;
use Carbon\Carbon;

class RefereeReleaseStrategy extends BaseReleaseStrategy implements ReleaseStrategyInterface
{
    private Releasable $releasable;

    public function __construct(Releasable $releasable)
    {
        $this->releasable = $releasable;
    }

    public function release(Carbon $releasedAt = null)
    {
        throw_unless($this->releasable->canBeReleased(), new CannotBeReleasedException);

        if ($this->releasable->isSuspended()) {
            RefereeReinstateStrategy::handle($this->releasable);
        }

        if ($this->releasable->isInjured()) {
            RefereeClearInjuryStrategy::handle($this->releasable);
        }

        $releaseDate = Carbon::parse($releasedAt)->toDateTimeString() ?? now()->toDateTimeString();

        $this->releasable->currentEmployment->update(['ended_at' => $releaseDate]);
        $this->releasable->updateStatusAndSave();
    }
}
