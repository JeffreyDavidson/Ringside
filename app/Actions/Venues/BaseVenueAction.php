<?php

declare(strict_types=1);

namespace App\Actions\Venues;

use App\Repositories\VenueRepository;

abstract class BaseVenueAction
{
    /**
     * The repository to be used for wrestlers.
     *
     * @var \App\Repositories\VenueRepository
     */
    protected VenueRepository $venueRepository;

    /**
     * Create a new base venue action instance.
     *
     * @param  \App\Repositories\VenueRepository  $venueRepository
     */
    public function __construct(VenueRepository $venueRepository)
    {
        $this->venueRepository = $venueRepository;
    }
}
