<?php

namespace App\Strategies\Suspend;

interface SuspendStrategyInterface
{
    /**
     * Suspend a suspendable model.
     *
     * @param  string|null $suspensionDate
     * @return void
     */
    public function suspend(string $suspensionDate = null);
}
