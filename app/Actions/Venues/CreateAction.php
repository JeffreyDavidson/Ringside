<?php

declare(strict_types=1);

namespace App\Actions\Venues;

use App\Data\Shared\VenueData;
use App\Models\Shared\Venue;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateAction extends BaseVenueAction
{
    use AsAction;

    /**
     * Create a venue.
     *
     * This handles the complete venue creation workflow:
     * - Creates the venue record with location and facility details
     * - Establishes the venue as available for event hosting
     * - Sets up the foundation for future event bookings
     *
     * @param  VenueData  $venueData  The data transfer object containing venue information
     * @return Venue The newly created venue instance
     *
     * @example
     * ```php
     * $venueData = new VenueData([
     *     'name' => 'Madison Square Garden',
     *     'street_address' => '4 Pennsylvania Plaza',
     *     'city' => 'New York',
     *     'state' => 'NY',
     *     'zipcode' => '10001'
     * ]);
     * $venue = CreateAction::run($venueData);
     * ```
     */
    public function handle(VenueData $venueData): Venue
    {
        return $this->venueRepository->create($venueData);
    }
}
