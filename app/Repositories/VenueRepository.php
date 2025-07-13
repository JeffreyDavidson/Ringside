<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\Shared\VenueData;
use App\Models\Shared\Venue;
use App\Repositories\Contracts\VenueRepositoryInterface;
use App\Repositories\Support\BaseRepository;
use Tests\Unit\Repositories\VenueRepositoryTest;

/**
 * Repository for Venue model business operations and data persistence.
 *
 * Handles all venue related database operations including CRUD operations
 * and address management functionality.
 *
 * @see VenueRepositoryTest
 */
class VenueRepository extends BaseRepository implements VenueRepositoryInterface
{
    /**
     * Create a new venue.
     */
    public function create(VenueData $venueData): Venue
    {
        return Venue::query()->create([
            'name' => $venueData->name,
            'street_address' => $venueData->street_address,
            'city' => $venueData->city,
            'state' => $venueData->state,
            'zipcode' => $venueData->zipcode,
        ]);
    }

    /**
     * Update a venue.
     */
    public function update(Venue $venue, VenueData $venueData): Venue
    {
        $venue->update([
            'name' => $venueData->name,
            'street_address' => $venueData->street_address,
            'city' => $venueData->city,
            'state' => $venueData->state,
            'zipcode' => $venueData->zipcode,
        ]);

        return $venue;
    }

    /**
     * Restore a soft-deleted venue.
     */
    public function restore(Venue $venue): void
    {
        $venue->restore();
    }
}
