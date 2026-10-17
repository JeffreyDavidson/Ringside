<?php

declare(strict_types=1);

namespace App\Builders\Events;

use App\Builders\Concerns\FiltersByEventId;
use App\Models\Events\EventPublication;
use App\Models\Scopes\PromotionContextScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * @template TModel of EventPublication
 *
 * @extends Builder<TModel>
 */
class EventPublicationBuilder extends Builder
{
    use FiltersByEventId;

    /**
     * A released publication named by a public link. A visitor has no promotion context, so the link alone selects it.
     */
    public function wherePublishedLink(string $publicId, string $slug): static
    {
        return $this->withoutGlobalScope(PromotionContextScope::class)
            ->where('public_id', $publicId)
            ->where('slug', $slug)
            ->whereNotNull('published_at');
    }

    /**
     * Keeps only publications whose event still exists in the publication's own promotion and loads that event.
     */
    public function withOwnerEvent(): static
    {
        return $this
            ->whereHas('event', fn (Builder $query): Builder => $query
                ->withoutGlobalScope(PromotionContextScope::class)
                ->whereColumn('events.promotion_id', 'event_publications.promotion_id'))
            ->with(['event' => fn (Relation $query): Relation => $query->withoutGlobalScope(PromotionContextScope::class)]);
    }
}
