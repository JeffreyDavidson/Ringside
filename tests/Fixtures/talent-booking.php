<?php

declare(strict_types=1);

use App\Actions\Events\SaveTalentBookingAction;
use App\Data\Events\TalentBookingData;
use App\Enums\Events\TalentBookingStatus;
use App\Models\Events\Event;
use App\Models\Events\EventTalentBooking;
use App\Models\Events\Venue;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Queries\Events\TalentBookingContextQuery;

use function Pest\Laravel\actingAs;

/** @return array{event: Event, person: Wrestler, booking: EventTalentBooking} */
function talentBookingFixture(): array
{
    actingAs(administrator());
    $event = Event::factory()->create(['name' => 'Harbor Showcase', 'date' => now()->addDay(), 'venue_id' => Venue::factory()]);
    $person = Wrestler::factory()->bookable()->create(['name' => 'Talent Cedar']);
    $booking = resolve(SaveTalentBookingAction::class)->handle($event, 'wrestler', $person->id, 0,
        resolve(TalentBookingContextQuery::class)->fingerprint($event, "wrestler:{$person->id}"),
        new TalentBookingData(TalentBookingStatus::Confirmed, 123456, now()->utc(), 'UTC', 'LEAK-TRAVEL-7Q', true));

    return ['event' => $event, 'person' => $person, 'booking' => $booking->refresh()];
}

/**
 * Save against the current context, keeping the fixture's individual identity.
 *
 * @param  array{event: Event, person: Wrestler, booking: EventTalentBooking}  $fixture
 */
function saveTalentFixture(array $fixture, TalentBookingData $data, ?int $version = null): EventTalentBooking
{
    return resolve(SaveTalentBookingAction::class)->handle($fixture['event'], 'wrestler', $fixture['person']->id,
        $version ?? $fixture['booking']->refresh()->version,
        resolve(TalentBookingContextQuery::class)->fingerprint($fixture['event']->refresh(), "wrestler:{$fixture['person']->id}"), $data)->refresh();
}
