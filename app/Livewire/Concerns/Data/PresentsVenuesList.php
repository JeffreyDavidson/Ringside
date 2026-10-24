<?php

declare(strict_types=1);

namespace App\Livewire\Concerns\Data;

use App\Builders\Events\VenueBuilder;
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
        return $this->venuesQuery()
            ->alphabetical()
            ->pluck('name', 'id')
            ->mapWithKeys(
                static fn (mixed $name, int|string $id): array => [$id => is_string($name) ? $name : null],
            )
            ->all();
    }

    /**
     * Which venues the list offers; every component that shows the list decides this for itself.
     *
     * @return VenueBuilder<Venue>
     */
    abstract protected function venuesQuery(): VenueBuilder;
}
