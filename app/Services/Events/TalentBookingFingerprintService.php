<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Models\Events\Event;

class TalentBookingFingerprintService
{
    public function __construct(private readonly TalentBookingContextService $context, private readonly EventPackCardService $card) {}

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
        return hash('sha256', $this->signature($this->context->get($event)).':'.(int) $this->onCard($event, $key));
    }
}
