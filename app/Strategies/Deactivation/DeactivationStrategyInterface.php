<?php

namespace App\Strategies\Deactivation;

interface DeactivationStrategyInterface
{
    /**
     * Deactivate a deactivatable model.
     *
     * @param  string|null $endedAt
     */
    public function deactivate(string $endedAt = null);

    /**
     * Determine if the deactivatable can be deactivated.
     *
     * @return bool
     */
    public function canBeDeactivated();
}
