<?php

namespace App\Services;

use App\Models\Venue;
use App\Repositories\VenueRepository;

class VenueService
{
    /**
     * The repository implementation.
     *
     * @var \App\Repositories\VenueRepository
     */
    protected $venueRepository;

    /**
     * Create a new venue service instance.
     *
     * @param \App\Repositories\VenueRepository $venueRepository
     */
    public function __construct(VenueRepository $venueRepository)
    {
        $this->venueRepository = $venueRepository;
    }

    /**
     * Create a new venue.
     *
     * @param  array $data
     * @return \App\Models\Venue $venue
     */
    public function create(array $data): Venue
    {
        $venue = $this->venueRepository->create($data);

        return $venue;
    }
}
