<?php

namespace App\Strategies\Employment;

interface EmploymentStrategyInterface
{
    /**
     * Employ an employable model.
     *
     * @param  string|null $startedAt
     * @return void
     */
    public function employ(string $startedAt = null);
}
