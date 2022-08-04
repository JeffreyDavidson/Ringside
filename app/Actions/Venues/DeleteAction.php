<?php

declare(strict_types=1);

namespace App\Actions\Venues;

use App\Models\Venue;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteAction extends BaseVenueAction
{
    use AsAction;

    /**
     * Delete a venue.
     *
     * @param  \App\Models\Venue  $venue
     * @return void
     */
    public function handle(Venue $venue): void
    {
        $this->venueRepository->delete($venue);
    }
}
