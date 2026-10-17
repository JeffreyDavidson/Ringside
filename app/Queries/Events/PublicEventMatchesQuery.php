<?php

declare(strict_types=1);

namespace App\Queries\Events;

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Scopes\PromotionContextScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;

/** The matches of an event in card order with what the public page prints: stipulation, titles, sides and their competitors. */
class PublicEventMatchesQuery
{
    /**
     * Public reads explicitly constrain ownership, independently of a visitor's authenticated context.
     *
     * @return Collection<int, EventMatch>
     */
    public function get(Event $event): Collection
    {
        $owned = fn (Builder $query): Builder => $query->withoutGlobalScope(PromotionContextScope::class)->where('promotion_id', $event->promotion_id);

        return EventMatch::query()->withoutGlobalScope(PromotionContextScope::class)->forEventId($event->id)->inCardOrder()
            ->with(['matchStipulation', 'titles' => fn (Relation $query): Builder => $owned($query->getQuery())->orderBy('titles.id'), 'sides.competitors'])->get();
    }
}
