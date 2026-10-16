<?php

declare(strict_types=1);

namespace App\Queries\Events;

use App\Models\Events\Event;
use App\Models\Events\EventPublication;
use App\Models\Scopes\PromotionContextScope;

class PublishedEventQuery
{
    /** The published publication for a public link, with its event, or null when the link names nothing published. */
    public function get(string $publicId, string $slug): ?EventPublication
    {
        $publication = EventPublication::query()->withoutGlobalScope(PromotionContextScope::class)->where('public_id', $publicId)->where('slug', $slug)->whereNotNull('published_at')->first();
        if (! $publication instanceof EventPublication) {
            return null;
        }
        $event = Event::query()->withoutGlobalScope(PromotionContextScope::class)->whereKey($publication->event_id)->where('promotion_id', $publication->promotion_id)->first();
        if (! $event instanceof Event) {
            return null;
        }

        return $publication->setRelation('event', $event);
    }
}
