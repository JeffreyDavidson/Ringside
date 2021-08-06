<?php

namespace App\Strategies\Suspend;

interface SuspendStrategyInterface
{
    /**
     * Suspend a suspendable model.
     *
     * @param  string|null $suspendedAt
     * @return void
     */
    public function suspend(string $suspendedAt = null);
}
