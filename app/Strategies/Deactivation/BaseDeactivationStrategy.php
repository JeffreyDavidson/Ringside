<?php

namespace App\Strategies\Deactivation;

use App\Exceptions\CannotBeDeactivatedException;
use App\Models\Contracts\Deactivatable;
use Carbon\Carbon;

class BaseDeactivationStrategy implements DeactivationStrategyInterface
{
    private Deactivatable $deactivatable;

    public function __construct(Deactivatable $deactivatable)
    {
        $this->deactivatable = $deactivatable;
    }

    public function deactivate(Carbon $startedAt = null)
    {
        throw_unless($this->deactivatable->canBeDeactivated(), new CannotBeDeactivatedException);

        $this->deactivatable->activations()->updateOrCreate(['ended_at' => null], ['started_at' => $startedAt ?? now()]);
        $this->deactivatable->updateStatusAndSave();
    }
}
