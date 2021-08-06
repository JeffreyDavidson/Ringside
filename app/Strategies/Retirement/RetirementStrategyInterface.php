<?php

namespace App\Strategies\Retirement;

interface RetirementStrategyInterface
{
    /**
     * Retire a retirable model.
     *
     * @param  string|null $retiredAt
     * @return void
     */
    public function retire(string $retiredAt = null);
}
