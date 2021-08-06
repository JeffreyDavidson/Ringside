<?php

namespace App\Strategies\Employment;

use Carbon\Carbon;

interface EmploymentStrategyInterface
{
    /**
     * Employ an employable model.
     *
     * @param  \Carbon\Carbon|null $startedAt
     * @return void
     */
    public function employ(Carbon $startedAt = null);
}
