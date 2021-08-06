<?php

namespace App\Strategies\Reinstate;

use App\Exceptions\CannotBeReinstatedException;
use App\Models\Contracts\Reinstatable;

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
     * @param  string|null $reinstatedAt
     * @return void
     */
    public function reinstate(string $reinstatedAt = null)
    {
        throw_unless($this->reinstatable->canBeReinstated(), new CannotBeReinstatedException);

        $reinstatedDate = $reinstatedAt ?: now();

        $this->repository->reinstate($this->reinstatable, $reinstatedDate);
        $this->reinstatable->currentWrestlers->each->reinstate($reinstatedDate);
        $this->reinstatable->updateStatusAndSave();
    }
}
