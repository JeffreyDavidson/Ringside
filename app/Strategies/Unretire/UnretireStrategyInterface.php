<?php

namespace App\Strategies\Unretire;

interface UnretireStrategyInterface
{
    /**
     * Unretire an unretirable model.
     *
     * @param  string|null $unretiredDate
     * @return void
     */
    public function unretire(string $unretiredDate = null);
}
