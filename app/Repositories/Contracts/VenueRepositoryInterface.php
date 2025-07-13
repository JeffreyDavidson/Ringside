<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Data\Shared\VenueData;
use App\Models\Shared\Venue;

interface VenueRepositoryInterface
{
    // CRUD operations
    public function create(VenueData $venueData): Venue;

    public function update(Venue $venue, VenueData $venueData): Venue;

    public function delete(Venue $venue): void;

    public function restore(Venue $venue): void;
}
