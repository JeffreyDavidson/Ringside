<?php

namespace App\Strategies\Reinstate;

use App\Exceptions\CannotBeReinstatedException;
use App\Models\Contracts\Reinstatable;
use App\Repositories\ManagerRepository;

class ManagerReinstateStrategy extends BaseReinstateStrategy implements ReinstateStrategyInterface
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
     * @var \App\Repositories\ManagerRepository
     */
    private ManagerRepository $managerRepository;

    /**
     * Create a new manager reinstate strategy instance.
     *
     * @param \App\Models\Contracts\Reinstatable $reinstatable
     */
    public function __construct(Reinstatable $reinstatable)
    {
        $this->reinstatable = $reinstatable;
        $this->managerRepository = new ManagerRepository;
    }

    /**
     * Reinstate a reinstatable model.
     *
     * @param  string|null $reinstatedDate
     * @return void
     */
    public function reinstate(string $reinstatedDate = null)
    {
        throw_unless($this->reinstatable->canBeReinstated(), new CannotBeReinstatedException);

        $reinstatedDate = $reinstatedDate ?: now()->toDateTimeString();

        $this->managerRepository->reinstate($this->reinstatable, $reinstatedDate);
        $this->reinstatable->updateStatusAndSave();
    }
}
