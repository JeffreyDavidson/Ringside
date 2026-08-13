<?php

declare(strict_types=1);

namespace App\Data\Wrestlers;

use App\Models\Managers\Manager;
use App\ValueObjects\Height;
use App\ValueObjects\Weight;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class WrestlerData
{
    public Height $height;

    public Weight $weight;

    /**
     * Create a new wrestler data instance.
     *
     * @param  string  $name  Wrestler's ring name
     * @param  Height|int  $height  Wrestler's height or height in inches
     * @param  Weight|int  $weight  Wrestler's weight or weight in pounds
     * @param  string  $hometown  Wrestler's hometown/origin
     * @param  string|null  $signature_move  Wrestler's signature finishing move
     * @param  Carbon|null  $employment_date  Employment start date (if provided, wrestler will be employed)
     * @param  Collection<int, Manager>|null  $managers  Collection of Manager models to assign to this wrestler
     */
    public function __construct(
        public string $name,
        Height|int $height,
        Weight|int $weight,
        public string $hometown,
        public ?string $signature_move,
        public ?Carbon $employment_date,
        public ?Collection $managers = null,
    ) {
        $this->height = $height instanceof Height ? $height : Height::fromInches($height);
        $this->weight = $weight instanceof Weight ? $weight : new Weight($weight);
    }

    /**
     * Check if wrestler has any managers assigned.
     */
    public function hasManagers(): bool
    {
        return $this->managers !== null && $this->managers->isNotEmpty();
    }
}
