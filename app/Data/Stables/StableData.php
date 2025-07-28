<?php

declare(strict_types=1);

namespace App\Data\Stables;

use Illuminate\Support\Carbon;

readonly class StableData
{
    /**
     * Create a new stable data instance.
     */
    public function __construct(
        public string $name,
        public ?Carbon $start_date,
        public StableMembershipData $members,
    ) {}
}
