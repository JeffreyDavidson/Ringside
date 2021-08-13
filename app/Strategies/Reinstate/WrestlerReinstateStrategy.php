<?php

namespace App\Strategies\Reinstate;

use App\Exceptions\CannotBeReinstatedException;
use App\Models\Contracts\Reinstatable;
use App\Repositories\WrestlerRepository;

class WrestlerReinstateStrategy extends BaseReinstateStrategy implements ReinstateStrategyInterface
{
    /**
     * The interface implementation.
     *
     * @var \App\Models\Contracts\Reinstatable
     */
    private Reinstatable $reinstatable;

    /**
     * The repository implementation.
     *
     * @var \App\Repositories\WrestlerRepository
     */
    private WrestlerRepository $wrestlerRepository;

    /**
     * Create a new wrestler reinstate strategy instance.
     */
    public function __construct()
    {
        $this->wrestlerRepository = new WrestlerRepository;
    }

    /**
     * Undocumented function.
     *
     * @param  \App\Models\Contracts\Reinstatable $reinstatable
     * @return $this
     */
    public function setReinstatable(Reinstatable $reinstatable)
    {
        $this->reinstatable = $reinstatable;

        return $this;
    }

    /**
     * Reinstate a reinstatable model.
     *
     * @param  string|null $reinstatementDate
     * @return void
     */
    public function reinstate(string $reinstatementDate = null)
    {
        throw_unless($this->reinstatable->canBeReinstated(), new CannotBeReinstatedException);

        $reinstatementDate ??= now()->toDateTimeString();

        $this->wrestlerRepository->reinstate($this->reinstatable, $reinstatementDate);
        $this->reinstatable->updateStatusAndSave();

        if ($this->reinstatable->currentTagTeam) {
            $this->reinstatable->currentTagTeam->updateStatusAndSave();
        }
    }
}
