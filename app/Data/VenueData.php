<?php

declare(strict_types=1);

namespace App\Data;

readonly class VenueData
{
    /**
     * Create a new venue data instance.
     */
    public function __construct(
        public string $name,
        public string $street_address,
        public string $city,
        public string $state,
        public string $zipcode,
    ) {}
}
