<?php

declare(strict_types=1);

namespace App\Queries\Events;

use App\Builders\Events\VenueBuilder;
use App\Models\Events\Venue;
use App\Support\Search\LikeTerm;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/** Finds the first 20 venues whose name contains the search term, A to Z. */
class VenueSearchQuery
{
    public const int LIMIT = 20;

    /**
     * @param  ?int  $storedVenueId  The edited event's own venue, which stays findable even if it was deleted since.
     * @return Collection<int, Venue>
     */
    public function get(string $term, ?int $storedVenueId = null): Collection
    {
        $like = LikeTerm::from($term);

        return Venue::query()
            ->when($storedVenueId !== null, fn (VenueBuilder $query): VenueBuilder => $query->withTrashed()->where(
                fn (Builder $query): Builder => $query->whereNull('deleted_at')->orWhere('id', $storedVenueId),
            ))
            ->when(! $like->isEmpty(), fn (VenueBuilder $query): VenueBuilder => $query->whereLike('name', $like->contains(), caseSensitive: false))
            ->alphabetical()
            ->orderBy('id')
            ->limit(self::LIMIT)
            ->get();
    }
}
