<?php

namespace App\Strategies\Reinstate;

interface ReinstateStrategyInterface
{
    /**
     * Reinstate a reinstatable model.
     *
     * @param  string|null $reinstatementDate
     * @return void
     */
    public function reinstate(string $reinstatementDate = null);
}
