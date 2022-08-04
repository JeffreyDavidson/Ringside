<?php

declare(strict_types=1);

namespace App\Actions\Venues;

use App\Data\VenueData;
use App\Models\Venue;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateAction extends BaseVenueAction
{
    use AsAction;

    /**
     * Update a venue.
     *
     * @param  \App\Models\Venue  $venue
     * @param  \App\Data\VenueData  $venueData
     * @return \App\Models\Venue
     */
    public function handle(Venue $venue, VenueData $venueData): Venue
    {
        return $this->venueRepository->update($venue, $venueData);
    }
}
