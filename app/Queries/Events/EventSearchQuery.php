<?php

declare(strict_types=1);

namespace App\Queries\Events;

use App\Builders\Events\EventBuilder;
use App\Models\Events\Event;
use App\Support\Search\LikeTerm;
use Illuminate\Database\Eloquent\Collection;

/** Finds the first 20 events of one promotion whose name contains the search term, newest first. */
class EventSearchQuery
{
    public const int LIMIT = 20;

    /** @return Collection<int, Event> */
    public function get(string $term, int $promotionId): Collection
    {
        $like = LikeTerm::from($term);

        return Event::query()
            ->where('promotion_id', $promotionId)
            ->when(! $like->isEmpty(), fn (EventBuilder $query): EventBuilder => $query->whereLike('name', $like->contains(), caseSensitive: false))
            ->latestDatedFirst()
            ->orderBy('id')
            ->limit(self::LIMIT)
            ->get();
    }
}
