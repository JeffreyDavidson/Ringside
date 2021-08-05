<?php

namespace App\Strategies\Reinstate;

use App\Exceptions\CannotBeReinstatedException;
use App\Models\Contracts\Reinstatable;
use Carbon\Carbon;

class ManagerReinstateStrategy extends BaseReinstateStrategy implements ReinstateStrategyInterface
{
    private Reinstatable $reinstatable;

    public function __construct(Reinstatable $reinstatable)
    {
        $this->reinstatable = $reinstatable;
    }

    public function reinstate(Carbon $reinstatedAt = null)
    {
        throw_unless($this->reinstatable->canBeReinstated(), new CannotBeReinstatedException);

        $reinstatedDate = Carbon::parse($reinstatedAt)->toDateTimeString() ?: now()->toDateTimeString();

        $this->reinstatable->currentSuspension()->update(['ended_at' => $reinstatedDate]);
        $this->reinstatable->updateStatusAndSave();
    }
}
