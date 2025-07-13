<?php

declare(strict_types=1);

namespace App\Data\Managers;

use Illuminate\Support\Carbon;

readonly class ManagerData
{
    /**
     * Create a new manager data instance.
     */
    public function __construct(
        public string $first_name,
        public string $last_name,
        public ?Carbon $employment_date,
    ) {}
}
