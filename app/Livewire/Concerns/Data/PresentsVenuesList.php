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
            ->pluck('name', 'id')
            ->mapWithKeys(
                static fn (mixed $name, int|string $id): array => [$id => is_string($name) ? $name : null]
            )
            ->all();
    }
}
