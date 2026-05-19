<?php

declare(strict_types=1);

namespace App\Data\Wrestlers;

use App\Models\Managers\Manager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class WrestlerData
{
    /**
     * Create a new wrestler data instance.
     *
     * @param  string  $name  Wrestler's ring name
     * @param  int  $height  Wrestler's height in inches
     * @param  int  $weight  Wrestler's weight in pounds
     * @param  string  $hometown  Wrestler's hometown/origin
     * @param  string|null  $signature_move  Wrestler's signature finishing move
     * @param  Carbon|null  $employment_date  Employment start date (if provided, wrestler will be employed)
     * @param  Collection<int, Manager>|null  $managers  Collection of Manager models to assign to this wrestler
     */
    public function __construct(
        public string $name,
        public int $height,
        public int $weight,
        public string $hometown,
        public ?string $signature_move,
        public ?Carbon $employment_date,
        public ?Collection $managers = null,
    ) {}

    /**
     * Check if wrestler has any managers assigned.
     */
    public function hasManagers(): bool
    {
        return $this->managers !== null && $this->managers->isNotEmpty();
    }
}
