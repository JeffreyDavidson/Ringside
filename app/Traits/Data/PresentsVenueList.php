<?php

declare(strict_types=1);

namespace App\Traits\Data;

use App\Models\Venue;
use Livewire\Attributes\Computed;

trait PresentsVenueList
{
    #[Computed(cache: true, key: 'venues-list', seconds: 180)]
    public function getVenues(): array
    {
        return Venue::all()->pluck('name', 'id')->toArray();
    }
}
