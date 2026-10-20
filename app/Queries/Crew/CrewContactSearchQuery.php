<?php

declare(strict_types=1);

namespace App\Queries\Crew;

use App\Builders\Crew\CrewContactBuilder;
use App\Models\Crew\CrewContact;
use Illuminate\Database\Eloquent\Collection;

/** Finds the first 20 crew contacts whose name contains the search term. */
class CrewContactSearchQuery
{
    /**
     * @param  ?int  $promotionId  Limits the search to one promotion; null relies on the active promotion context.
     * @return Collection<int, CrewContact>
     */
    public function get(string $term, ?int $promotionId = null, bool $includeArchived = false): Collection
    {
        return CrewContact::query()
            ->when($promotionId !== null, fn (CrewContactBuilder $query) => $query->where('promotion_id', $promotionId))
            ->when(! $includeArchived, fn (CrewContactBuilder $query) => $query->whereNull('archived_at'))
            ->matchingName($term)
            ->orderBy('name')
            ->orderBy('id')
            ->limit(20)
            ->get();
    }
}
