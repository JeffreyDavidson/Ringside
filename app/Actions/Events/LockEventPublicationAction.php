<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Exceptions\Events\EventPublicationException;
use App\Exceptions\RecordChangedException;
use App\Lifecycle\Matches\MatchSchedulingLock;
use App\Models\Events\Event;
use App\Models\Events\EventPublication;
use App\Models\Matches\EventMatch;
use App\Models\Scopes\PromotionContextScope;
use App\Queries\Events\PublicEventSourceQuery;
use Illuminate\Support\Str;

/** @phpstan-import-type Source from PublicEventSourceQuery */
class LockEventPublicationAction
{
    public function __construct(private readonly MatchSchedulingLock $locks, private readonly PublicEventSourceQuery $source) {}

    /** Must run inside the caller's transaction; the event lock also serializes first-draft creation.
     * @return array{event: Event, publication: EventPublication, source: Source}
     */
    public function handle(Event $event, int $version, string $fingerprint): array
    {
        $this->locks->lockEventSet($event->id);
        $event = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();
        if ($event->promotion_id === null) {
            throw EventPublicationException::invalid();
        }
        EventMatch::query()->withTrashed()->forEventId($event->id)->orderBy('id')->lockForUpdate()->get();
        $publication = EventPublication::query()->withoutGlobalScope(PromotionContextScope::class)->where('event_id', $event->id)->lockForUpdate()->first();
        $source = $this->source->get($event);
        if (($publication->version ?? 0) !== $version || ! hash_equals($source['fingerprint'], $fingerprint)) {
            throw RecordChangedException::changed();
        }
        if ($publication !== null && $publication->promotion_id !== $event->promotion_id) {
            throw EventPublicationException::invalid();
        }
        $publication ??= new EventPublication;
        if (! $publication->exists) {
            $publication->event_id = $event->id;
            $publication->promotion_id = $event->promotion_id;
            $publication->public_id = (string) Str::uuid();
            $publication->slug = Str::limit(Str::slug($event->name), 120, '') ?: 'event';
            $publication->version = 0;
        }

        return ['event' => $event, 'publication' => $publication, 'source' => $source];
    }
}
