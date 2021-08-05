<?php

namespace App\Strategies\Reinstate;

use App\Exceptions\CannotBeReinstatedException;
use App\Models\Contracts\Reinstatable;
use Carbon\Carbon;

class TagTeamReinstateStrategy extends BaseReinstateStrategy implements ReinstateStrategyInterface
{
    private Reinstatable $reinstatable;

    public function __construct(Reinstatable $reinstatable)
    {
        $this->reinstatable = $reinstatable;
    }

    public function reinstate(Carbon $reinstatedAt = null)
    {
        throw_unless($this->reinstatable->canBeReinstated(), new CannotBeReinstatedException);

        $reinstatedDate = $reinstatedAt ?: now();

        $this->reinstatable->currentSuspension()->update(['ended_at' => $reinstatedDate]);
        $this->reinstatable->currentWrestlers->each->reinstate($reinstatedDate);
        $this->reinstatable->updateStatusAndSave();
    }
}
