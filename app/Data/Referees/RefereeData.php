<?php

declare(strict_types=1);

namespace App\Data\Referees;

use Illuminate\Support\Carbon;

readonly class RefereeData
{
    /**
     * Create a new referee data instance.
     */
    public function __construct(
        public string $first_name,
        public string $last_name,
        public ?Carbon $employment_date,
    ) {}
}
