<?php

namespace App\Strategies\ClearInjury;

interface ClearInjuryStrategyInterface
{
    /**
     * Clear an injury of an injurable model.
     *
     * @param  string|null $recoveryDate
     * @return void
     */
    public function clearInjury(string $recoveryDate = null);
}
