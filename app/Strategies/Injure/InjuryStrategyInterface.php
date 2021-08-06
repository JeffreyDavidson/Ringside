<?php

namespace App\Strategies\Injure;

interface InjuryStrategyInterface
{
    /**
     * Injure an injurable model.
     *
     * @param  string|null $injuredAt
     * @return void
     */
    public function injure(string $injuredAt = null);
}
