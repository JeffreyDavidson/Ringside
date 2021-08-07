<?php

namespace App\Strategies\Retirement;

interface RetirementStrategyInterface
{
    /**
     * Retire a retirable model.
     *
     * @param  string|null $retirementDate
     * @return void
     */
    public function retire(string $retirementDate = null);
}
