<?php

declare(strict_types=1);

namespace App\Queries\Events;

use App\Models\Events\EventPublication;

class PublishedEventQuery
{
    /** The published publication for a public link, with its event, or null when the link names nothing published. */
    public function get(string $publicId, string $slug): ?EventPublication
    {
        return EventPublication::query()->wherePublishedLink($publicId, $slug)->withOwnerEvent()->first();
    }
}
