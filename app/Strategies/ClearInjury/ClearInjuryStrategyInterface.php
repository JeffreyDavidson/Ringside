<?php

namespace App\Strategies\ClearInjury;

interface ClearInjuryStrategyInterface
{
    /**
     * Clear an injury of an injurable model.
     *
     * @param  string|null $recoveredAt
     * @return void
     */
    public function clearInjury(string $recoveredAt = null);
}
