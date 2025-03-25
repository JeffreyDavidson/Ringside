<?php

declare(strict_types=1);

namespace App\Actions\Venues;

use App\Repositories\VenueRepository;

abstract class BaseVenueAction
{
    /**
     * Create a new base venue action instance.
     */
    public function __construct(protected VenueRepository $venueRepository) {}
}
