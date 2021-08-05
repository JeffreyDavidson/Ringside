<?php

namespace App\Strategies\Activation;

use App\Exceptions\CannotBeActivatedException;
use App\Models\Contracts\Activatable;
use Carbon\Carbon;

class BaseActivationStrategy implements ActivationStrategyInterface
{
    private Activatable $activatable;

    public function __construct(Activatable $activatable)
    {
        $this->activatable = $activatable;
    }

    public function activate(Carbon $startedAt = null)
    {
        throw_unless($this->activatable->canBeActivated(), new CannotBeActivatedException);

        $this->activatable->activations()->updateOrCreate(['ended_at' => null], ['started_at' => $startedAt ?? now()]);
        $this->activatable->updateStatusAndSave();
    }
}
