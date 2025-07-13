<?php

declare(strict_types=1);

namespace App\Actions\Venues;

use App\Data\Shared\VenueData;
use App\Models\Shared\Venue;
use App\Repositories\VenueRepository;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateAction extends BaseVenueAction
{
    use AsAction;

    public function __construct(
        VenueRepository $venueRepository
    ) {
        parent::__construct($venueRepository);
    }

    /**
     * Update a venue.
     *
     * This handles the complete venue update workflow:
     * - Updates venue location and facility information
     * - Maintains data integrity for existing event bookings
     * - Preserves venue history and event associations
     *
     * @param  Venue  $venue  The venue to update
     * @param  VenueData  $venueData  The updated venue information
     * @return Venue The updated venue instance
     *
     * @example
     * ```php
     * $venueData = new VenueData([
     *     'name' => 'Updated Arena Name',
     *     'street_address' => 'New Address'
     * ]);
     * $updatedVenue = UpdateAction::run($venue, $venueData);
     * ```
     */
    public function handle(Venue $venue, VenueData $venueData): Venue
    {
        return $this->venueRepository->update($venue, $venueData);
    }
}
