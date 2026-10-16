<?php

declare(strict_types=1);

namespace App\Lifecycle\Events;

use App\Exceptions\Events\LiveShowException;
use App\Models\Events\Event;
use App\Models\Events\EventLiveRun;
use App\Queries\Events\EventPackSourceQuery;
use App\Queries\Events\LiveShowSourceQuery;

/**
 * A live show can only be prepared, reviewed or run from a dated event whose saved pack matches its card.
 *
 * @phpstan-import-type Source from LiveShowSourceQuery
 */
final readonly class LiveShowSourceRequirements
{
    public function __construct(private LiveShowSourceQuery $source, private EventPackSourceQuery $pack) {}

    /** @return Source */
    public function ensureSource(Event $event): array
    {
        return $this->source->get($event) ?? throw LiveShowException::prepareRequired();
    }

    /** Whether the saved pack or the card moved on since the run was prepared or last reviewed. */
    public function hasChanged(Event $event, EventLiveRun $run): bool
    {
        $data = $this->pack->get($event);

        return $data['pack'] === null || $data['pack']->version !== $run->pack_version
            || ! hash_equals($run->card_fingerprint, $data['card']['fingerprint']) || $data['needsReview'];
    }
}
