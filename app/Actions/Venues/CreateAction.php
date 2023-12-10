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
     */
    public function handle(VenueData $venueData): Venue
    {
        return $this->venueRepository->create($venueData);
    }
}
