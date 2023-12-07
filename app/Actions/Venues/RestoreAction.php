<?php

declare(strict_types=1);

namespace App\Actions\Venues;

use App\Models\Venue;
use Lorisleiva\Actions\Concerns\AsAction;

class RestoreAction extends BaseVenueAction
{
    use AsAction;

    /**
     * Restore a venue.
     */
    public function handle(Venue $venue): void
    {
        $this->venueRepository->restore($venue);
    }
}
