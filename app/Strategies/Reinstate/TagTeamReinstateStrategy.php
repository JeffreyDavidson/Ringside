<?php

namespace App\Strategies\Reinstate;

use App\Exceptions\CannotBeReinstatedException;
use App\Models\Contracts\Reinstatable;
use Carbon\Carbon;

class TagTeamReinstateStrategy extends BaseReinstateStrategy implements ReinstateStrategyInterface
{
    /**
     * The interface implementation.
     *
     * @var \App\Models\Contracts\Reinstatable
     */
    private Reinstatable $reinstatable;

    /**
     * Create a new tag team reinstate strategy instance.
     *
     * @param \App\Models\Contracts\Reinstatable $reinstatable
     */
    public function __construct(Reinstatable $reinstatable)
    {
        $this->reinstatable = $reinstatable;
    }

    /**
     * Reinstate a reinstatable model.
     *
     * @param  \Carbon\Carbon|null $reinstatedAt
     * @return void
     */
    public function reinstate(Carbon $reinstatedAt = null)
    {
        throw_unless($this->reinstatable->canBeReinstated(), new CannotBeReinstatedException);

        $reinstatedDate = $reinstatedAt ?: now();

        $this->reinstatable->currentSuspension()->update(['ended_at' => $reinstatedDate]);
        $this->reinstatable->currentWrestlers->each->reinstate($reinstatedDate);
        $this->reinstatable->updateStatusAndSave();
    }
}
