<?php

declare(strict_types=1);

namespace App\Data\Events;

use App\Enums\Shared\UnitedStatesState;
use App\ValueObjects\Address;

readonly class VenueData
{
    public Address $address;

    /**
     * Create a new venue data instance.
     */
    public function __construct(
        public string $name,
        public string $street_address,
        public string $city,
        public string $state,
        public string $zipcode,
    ) {
        $this->address = new Address($street_address, $city, UnitedStatesState::from($state), $zipcode);
    }
}
