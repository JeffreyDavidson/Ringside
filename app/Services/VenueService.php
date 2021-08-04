<?php

namespace App\Services;

use App\Models\Venue;
use App\Repositories\VenueRepository;

class VenueService
{
    protected $venueRepository;

    public function __construct(VenueRepository $venueRepository)
    {
        $this->venueRepository = $venueRepository;
    }

    /**
     * Creates a new tag team.
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
