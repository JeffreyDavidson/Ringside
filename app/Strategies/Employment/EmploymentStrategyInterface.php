<?php

namespace App\Strategies\Employment;

interface EmploymentStrategyInterface
{
    /**
     * Employ an employable model.
     *
     * @param  string|null $employmentDate
     * @return void
     */
    public function employ(string $employmentDate = null);
}
