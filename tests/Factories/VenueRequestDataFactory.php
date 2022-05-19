<?php

declare(strict_types=1);

namespace Tests\Factories;

use App\Models\Venue;

class VenueRequestDataFactory
{
    private string $name = 'Example Venue Name';

    private string $address1 = '123 Main Street';

    private ?string $address2 = 'Suite 100';

    private string $city = 'Laraville';

    private string $state = 'New York';

    private string $zip = '12345';

    public static function new(): self
    {
        return new self;
    }

    public function create(array $overrides = []): array
    {
        return array_replace([
            'name' => $this->name,
            'address1' => $this->address1,
            'address2' => $this->address2,
            'city' => $this->city,
            'state' => $this->state,
            'zip' => $this->zip,
        ], $overrides);
    }

    public function withVenue(Venue $venue): self
    {
        $clone = clone $this;

        $clone->name = $venue->name;
        $clone->address1 = $venue->address1;
        $clone->address2 = $venue->address2;
        $clone->city = $venue->city;
        $clone->state = $venue->state;
        $clone->zip = $venue->zip;

        return $clone;
    }
}
