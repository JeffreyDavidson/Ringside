<?php

namespace App\Strategies\Reinstate;

interface ReinstateStrategyInterface
{
    /**
     * Reinstate a reinstatable model.
     *
     * @param  string|null $reinstatedAt
     * @return void
     */
    public function reinstate(string $reinstatedAt = null);
}
