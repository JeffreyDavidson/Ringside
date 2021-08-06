<?php

namespace App\Strategies\Deactivation;

use Carbon\Carbon;

interface DeactivationStrategyInterface
{
    /**
     * Deactivate a deactivatable model.
     *
     * @param  \Carbon\Carbon|null $startedAt
     */
    public function deactivate(Carbon $startedAt = null);

    /**
     * Determine if the deactivatable can be deactivated.
     *
     * @return bool
     */
    public function canBeDeactivated();
}
