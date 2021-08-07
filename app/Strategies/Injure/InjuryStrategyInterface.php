<?php

namespace App\Strategies\Injure;

interface InjuryStrategyInterface
{
    /**
     * Injure an injurable model.
     *
     * @param  string|null $injureDate
     * @return void
     */
    public function injure(string $injureDate = null);
}
