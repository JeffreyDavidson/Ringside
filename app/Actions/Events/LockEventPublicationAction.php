<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Enums\Users\UserStatus;
use App\Exceptions\Events\EventPublicationException;
use App\Exceptions\RecordChangedException;
use App\Models\Events\Event;
use App\Models\Events\EventPublication;
use App\Models\Matches\EventMatch;
use App\Models\Scopes\PromotionContextScope;
use App\Models\Users\User;
use App\Queries\Events\PublicEventSourceQuery;
use App\Services\Matches\MatchAssignmentConflictService;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/** @phpstan-import-type Source from PublicEventSourceQuery */
class LockEventPublicationAction
{
    public function __construct(private readonly MatchAssignmentConflictService $locks, private readonly PublicEventSourceQuery $source, private readonly PromotionContextService $context) {}

    /** Must run inside the caller's transaction; the event lock also serializes first-draft creation.
     * @return array{event: Event, publication: EventPublication, source: Source}
     */
    public function handle(Event $event, User $actor, int $version, string $fingerprint): array
    {
        $this->locks->lockEventSet($event->id);
        $event = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();
        $actor = $actor->refresh();
        $this->context->forgetMemberships();
        if ($actor->status !== UserStatus::Active) {
            throw new AuthorizationException;
        }
        Gate::forUser($actor)->authorize('preparePublication', $event);
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
