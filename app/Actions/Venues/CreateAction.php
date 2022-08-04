<?php

declare(strict_types=1);

namespace App\Actions\Venues;

use App\Data\VenueData;
use App\Models\Venue;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateAction extends BaseVenueAction
{
    use AsAction;

    /**
     * Create a venue.
     *
     * @param  \App\Data\VenueData  $venueData
     * @return \App\Models\Venue
     */
    public function handle(VenueData $venueData): Venue
    {
        return $this->venueRepository->create($venueData);
    }
}
