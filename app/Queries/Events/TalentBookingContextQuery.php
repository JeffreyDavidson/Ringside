<?php

declare(strict_types=1);

namespace App\Queries\Events;

use App\Enums\Lifecycle\LifecycleDimension;
use App\Models\Events\Event;
use App\Models\Promotions\Promotion;

class TalentBookingContextQuery
{
    public function __construct(private readonly EventPackCardQuery $card) {}

    /** @return array<string, mixed> */
    public function get(Event $event): array
    {
        $event->load('promotion');
        $venue = $event->venue()->withTrashed()->first();

        return ['event_name' => $event->name, 'event_date' => $event->date?->utc()->toIso8601String(),
            'timezone' => Promotion::zoneOf($event->promotion), 'venue_id' => $event->venue_id,
            'venue_name' => $venue?->name, 'venue_address' => $venue === null ? null : [$venue->street_address, $venue->city, $venue->state, $venue->zipcode],
            'venue_deleted' => $event->venue_id !== null && ($venue === null || $venue->trashed()),
            'deletion_version' => $event->lifecycleTransitions()->where('dimension', LifecycleDimension::Deletion)->orderByDesc('id')->first()->id ?? 0];
    }

    /**
     * Names are historical display context, not agreement invalidation.
     *
     * @param  array<string, mixed>  $context
     */
    public function signature(array $context): string
    {
        unset($context['event_name'], $context['venue_name']);
        ksort($context);

        return hash('sha256', json_encode($context, JSON_THROW_ON_ERROR));
    }

    public function onCard(Event $event, string $key): bool
    {
        return in_array($key, array_column($this->card->get($event)['roster'], 'key'), true);
    }

    public function fingerprint(Event $event, string $key): string
    {
        return hash('sha256', $this->signature($this->get($event)).':'.(int) $this->onCard($event, $key));
    }
}
