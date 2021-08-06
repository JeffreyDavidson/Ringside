<?php

namespace App\Strategies\Activation;

use App\Exceptions\CannotBeActivatedException;
use App\Models\Contracts\Activatable;
use Carbon\Carbon;

class BaseActivationStrategy implements ActivationStrategyInterface
{
    /**
     * The interface implementation.
     *
     * @var \App\Models\Contracts\Activatable
     */
    private Activatable $activatable;

    /**
     * Create a new base activation strategy instance.
     *
     * @param \App\Models\Contracts\Activatable $activatable
     */
    public function __construct(Activatable $activatable)
    {
        $this->activatable = $activatable;
    }

    /**
     * Activate an activatable model.
     *
     * @param  \Carbon\Carbon|null $startedAt
     * @return void
     */
    public function activate(Carbon $startedAt = null)
    {
        throw_unless($this->activatable->canBeActivated(), new CannotBeActivatedException);

        $this->activatable->activations()->updateOrCreate(['ended_at' => null], ['started_at' => $startedAt ?? now()]);
        $this->activatable->updateStatusAndSave();
    }
}
