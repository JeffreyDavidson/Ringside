<?php

declare(strict_types=1);

use App\Actions\Events\SaveEventProductionAction;
use App\Data\Events\EventProductionData;
use App\Models\Crew\CrewContact;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\ViewModels\Events\EventPackViewModel;

/** @return array{event: Event, contact: CrewContact, match: EventMatch, production: array{notes: ?string, assignments: list<array{id: string, contact_id: int, name: string, role: string, station: ?string, call_at: ?string, timezone: string, offset: ?string, setup: ?string, during: ?string, strike: ?string, active: bool}>, cues: list<array{id: string, item_key: string, subject: string, trigger: string, instruction: string, role: ?string, owner_id: ?string, owner_name: ?string, owner_station: ?string, owner_role: ?string, owner_contact_id: ?int, pronunciation: ?string, track: ?string, url: ?string}>}} */
function productionFixture(): array
{
    \Pest\Laravel\actingAs(administrator());
    $event = Event::factory()->for(Promotion::factory())->create(['date' => now()]);
    EventMatch::factory()->for($event)->create();
    $match = EventMatch::query()->forEventId($event->id)->sole();
    $contact = CrewContact::factory()->create(['promotion_id' => $event->promotion_id, 'name' => 'PRIVATE-CREW-7Q']);
    $data = resolve(EventPackViewModel::class)->data($event);
    resolve(SaveEventProductionAction::class)->handle($event, 0, $data['card']['fingerprint'], EventProductionData::fromValidated([
        'notes' => 'PRIVATE-NOTE-7Q',
        'assignments' => [['id' => 'eed36a82-90cb-4a79-b2cc-92e6c4051001', 'contact_id' => $contact->id, 'role' => 'sound', 'timezone' => 'UTC']],
        'cues' => [['id' => 'eed36a82-90cb-4a79-b2cc-92e6c4051003', 'item_key' => 'match:'.$match->id,
            'subject' => 'PRIVATE-SUBJECT-7Q', 'trigger' => 'Caller signal', 'instruction' => 'PRIVATE-CUE-7Q', 'owner_id' => 'eed36a82-90cb-4a79-b2cc-92e6c4051001']],
    ]), false);

    $content = $event->pack()->firstOrFail()->content;
    if (! isset($content['production'])) {
        throw new RuntimeException('Production fixture was not saved.');
    }

    return ['event' => $event, 'contact' => $contact, 'match' => $match, 'production' => $content['production']];
}
