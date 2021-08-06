<?php

namespace App\Strategies\Unretire;

interface UnretireStrategyInterface
{
    /**
     * Unretire an unretirable model.
     *
     * @param  string|null $unretiredAt
     * @return void
     */
    public function unretire(string $unretiredAt = null);
}
