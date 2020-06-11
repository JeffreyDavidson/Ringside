<?php

namespace App\ViewModels;

use App\Models\Venue;
use Spatie\ViewModels\ViewModel;

class VenueViewModel extends ViewModel
{
    /** @var $venue */
    public $venue;

    /**
     * Create a new venue view model instance.
     *
     * @param App\Models\Venue|null $venue
     */
    public function __construct(Venue $venue = null)
    {
        $this->venue = $venue ?? new Venue;
    }
}
