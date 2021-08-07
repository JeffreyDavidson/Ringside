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
}
