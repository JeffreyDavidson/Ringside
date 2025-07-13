<?php

declare(strict_types=1);

namespace App\Livewire\Concerns\Data;

use App\Models\Shared\Venue;
use Livewire\Attributes\Computed;

trait PresentsVenuesList
{
    /**
     * @return array<int|string,string|null>
     */
    #[Computed(cache: true, key: 'venues-list', seconds: 180)]
    public function getVenues(): array
    {
        return Venue::select('id', 'name')->pluck('name', 'id')->toArray();
    }
}
