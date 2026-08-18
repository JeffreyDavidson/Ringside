<?php

declare(strict_types=1);

namespace App\Livewire\Concerns\Data;

use App\Models\Events\Venue;
use Livewire\Attributes\Computed;

trait PresentsVenuesList
{
    /**
     * @return array<int|string,string|null>
     */
    #[Computed]
    public function getVenues(): array
    {
        return Venue::query()
            ->alphabetical()
            ->get(['id', 'name'])
            ->mapWithKeys(fn (Venue $venue): array => [$venue->id => $venue->name])
            ->all();
    }
}
