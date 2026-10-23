<?php

declare(strict_types=1);

require_once __DIR__.'/../../../../Fixtures/talent-booking.php';

use App\Data\Events\TalentBookingData;
use App\Enums\Events\TalentBookingStatus;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Livewire\Events\Components\TalentBookings;
use App\Models\Events\Event;
use App\Models\Events\EventLiveRun;
use App\Models\Events\EventTalentBooking;
use App\Models\Events\EventTalentBookingRevision;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Support\Facades\Gate;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

test('the editor records exact USD zero and a previous-day arrival without changing attendance', function (): void {
    // Arrange
    $fixture = talentBookingFixture();
    $component = livewire(TalentBookings::class, ['eventId' => $fixture['event']->id]);
    $component->call('edit', 'wrestler', $fixture['person']->id);

    // Act
    $component->set('form.fee', '0.00');
    $component->set('form.arrival', now()->utc()->format('Y-m-d\\TH:i'));
    $component->call('save', true);

    // Assert
    $component->assertHasNoErrors()->assertSee('Booking saved.');
    expect($fixture['booking']->refresh()->fee_minor)->toBe(0)
        ->and($fixture['booking']->arrival_at?->isBefore($fixture['event']->date ?? throw new LogicException('Scheduled fixture required')))->toBeTrue()
        ->and(EventLiveRun::query()->count())->toBe(0);
});

test('invalid form input is retained without booking or history changes', function (string $field, string $value): void {
    // Arrange
    $fixture = talentBookingFixture();
    $component = livewire(TalentBookings::class, ['eventId' => $fixture['event']->id]);
    $component->call('edit', 'wrestler', $fixture['person']->id);

    // Act
    $component->set("form.{$field}", $value);
    $component->call('save');

    // Assert
    $component->assertHasErrors(["form.{$field}"])->assertSet("form.{$field}", $value);
    expect($fixture['booking']->refresh()->version)->toBe(1)->and(EventTalentBookingRevision::query()->count())->toBe(1);
})->with([
    'negative fee' => ['fee', '-1'], 'fractional cent' => ['fee', '1.234'], 'too large' => ['fee', '100000000.00'],
    'invalid arrival' => ['arrival', 'not-a-date'], 'long note' => ['travelNotes', str_repeat('a', 2001)], 'long reason' => ['reason', str_repeat('a', 501)],
    'bad state' => ['status', 'unrecognised'],
]);

test('a stale editor preserves input and the newer saved agreement', function (): void {
    // Arrange
    $fixture = talentBookingFixture();
    $component = livewire(TalentBookings::class, ['eventId' => $fixture['event']->id]);
    $component->call('edit', 'wrestler', $fixture['person']->id);
    saveTalentFixture($fixture, new TalentBookingData(TalentBookingStatus::Contacted, null, null, 'UTC', null, false, 'Correction'));

    // Act
    $component->set('form.travelNotes', 'My unsaved note');
    $component->call('save', true);

    // Assert
    $component->assertHasErrors(['booking' => 'This was changed by someone else. Reload to see the latest version, review it, then try again. Your changes are still here.'])->assertSet('form.travelNotes', 'My unsaved note');
    expect($fixture['booking']->refresh()->version)->toBe(2)->and(EventTalentBookingRevision::query()->count())->toBe(2);
});

test('search is bounded within the event promotion and includes unavailable individuals', function (): void {
    // Arrange
    actingAs(administrator());
    $promotion = Promotion::factory()->create();
    $event = Event::factory()->for($promotion)->create();
    Wrestler::factory()->count(21)->for($promotion)->create(['name' => 'Needle Cedar']);
    $foreign = Wrestler::factory()->for(Promotion::factory())->create(['name' => 'Foreign Needle']);
    $component = livewire(TalentBookings::class, ['eventId' => $event->id]);

    // Act / Assert
    $component->call('searchRoster', 'wrestlers', 'Needle')->assertReturned(fn (array $options): bool => count($options) === 20);
    $component->call('searchRoster', 'wrestlers', 'Foreign')->assertReturned([]);
    $component->call('searchRoster', 'tag_teams', '')->assertReturned([]);
    $component->call('edit', 'wrestler', $foreign->id)->assertNotFound();
    expect(EventTalentBooking::query()->count())->toBe(0);
});

test('downgrading an editor blocks every protected operation before private state is returned', function (string $operation): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $event = Event::factory()->for($promotion)->create();
    $person = Wrestler::factory()->for($promotion)->create();
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => MembershipRole::Manager, 'status' => MembershipStatus::Active]);
    actingAs($user);
    get(route('events.talent-bookings.show', $event))->assertOk();
    $component = livewire(TalentBookings::class, ['eventId' => $event->id]);
    $component->call('edit', 'wrestler', $person->id);
    $promotion->users()->updateExistingPivot($user, ['role' => MembershipRole::Member]);
    resolve(PromotionContextService::class)->forgetMemberships();

    // Act
    $component->call($operation, ...match ($operation) {
        'searchRoster' => ['wrestlers', ''], 'edit', 'showHistory' => ['wrestler', $person->id], default => [],
    });

    // Assert
    $component->assertForbidden();
    expect(EventTalentBooking::query()->count())->toBe(0)->and(EventTalentBookingRevision::query()->count())->toBe(0);
})->with(['save', 'refreshState', 'searchRoster', 'edit', 'showHistory', 'closeEditor', 'addPerson']);

test('an administrator enforcing a different promotion cannot read or mutate private bookings', function (): void {
    // Arrange
    $fixture = talentBookingFixture();
    $context = resolve(PromotionContextService::class);
    $context->set(Promotion::factory()->create());
    $context->enforce();

    // Act / Assert
    livewire(TalentBookings::class, ['eventId' => $fixture['event']->id])->assertNotFound();
    expect(Gate::allows('update', $fixture['event']))->toBeFalse();
});

test('repeated-hour arrival edits retain the original stored instant', function (): void {
    // Arrange
    actingAs(administrator());
    $promotion = Promotion::factory()->create(['timezone' => 'America/New_York']);
    $event = Event::factory()->for($promotion)->create(['date' => '2026-11-02 20:00:00']);
    $person = Wrestler::factory()->for($promotion)->create();
    $booking = EventTalentBooking::factory()->for($event)->create(['person_id' => $person->id, 'arrival_at' => '2026-11-01 06:30:00', 'arrival_timezone' => 'America/New_York']);
    $component = livewire(TalentBookings::class, ['eventId' => $event->id]);
    $component->call('edit', 'wrestler', $person->id)->assertSet('form.arrival', '2026-11-01T01:30');

    // Act
    $component->set('form.travelNotes', 'Revised travel');
    $component->call('save');

    // Assert
    $component->assertHasNoErrors();
    expect($booking->refresh()->arrival_at?->toIso8601String())->toBe('2026-11-01T06:30:00+00:00');
});

test('a skipped local arrival time is rejected and preserved for correction', function (): void {
    // Arrange
    actingAs(administrator());
    $promotion = Promotion::factory()->create(['timezone' => 'America/New_York']);
    $event = Event::factory()->for($promotion)->create();
    $person = Wrestler::factory()->for($promotion)->create();
    $component = livewire(TalentBookings::class, ['eventId' => $event->id]);
    $component->call('edit', 'wrestler', $person->id);

    // Act
    $component->set('form.arrival', '2027-03-14T02:30');
    $component->call('save');

    // Assert
    $component->assertHasErrors(['form.arrival'])->assertSet('form.arrival', '2027-03-14T02:30');
    expect(EventTalentBooking::query()->count())->toBe(0);
});

test('role changes clear a stale search selection and referee results remain scoped', function (): void {
    // Arrange
    $fixture = talentBookingFixture();
    $referee = Referee::factory()->create(['first_name' => 'Cedar', 'last_name' => 'Official']);
    $component = livewire(TalentBookings::class, ['eventId' => $fixture['event']->id]);

    // Act
    $component->set('selectedPerson', $fixture['person']->id);
    $component->set('searchKind', 'referees');

    // Assert
    $component->assertSet('selectedPerson', null);
    $component->call('searchRoster', 'referees', 'Cedar')->assertReturned([['id' => $referee->id, 'name' => 'Cedar Official']]);

    // Act
    $component->set('searchKind', 'tag_teams');

    // Assert
    $component->assertSet('searchKind', 'wrestlers');
});

test('the private timeline can reach older agreements beyond the first page', function (): void {
    // Arrange
    $fixture = talentBookingFixture();
    saveTalentFixture($fixture, new TalentBookingData(TalentBookingStatus::Contacted, null, null, 'UTC', 'Correction', false, 'Correction'));

    foreach (range(1, 12) as $version) {
        saveTalentFixture($fixture, new TalentBookingData(TalentBookingStatus::Contacted, null, null, 'UTC', "Change {$version}"));
    }
    $component = livewire(TalentBookings::class, ['eventId' => $fixture['event']->id]);

    // Act
    $component->call('showHistory', 'wrestler', $fixture['person']->id);

    // Assert
    $component->assertSee('Change 12')->assertDontSee('LEAK-TRAVEL-7Q');

    // Act
    $component->call('nextPage', 'historyPage');

    // Assert
    $component->assertSee('LEAK-TRAVEL-7Q');
    $component->call('closeEditor')->assertSet('personId', 0)->assertDispatched('talent-editor-closed');
});

test('history shows an unknown stored agreement status as stored instead of failing', function (): void {
    // Arrange
    $fixture = talentBookingFixture();
    saveTalentFixture($fixture, new TalentBookingData(TalentBookingStatus::Contacted, null, null, 'UTC', 'Correction', false, 'Correction'));
    $revision = EventTalentBookingRevision::query()->latest('id')->firstOrFail();
    $snapshot = $revision->snapshot;
    $snapshot['status'] = 'legacy_status';
    EventTalentBookingRevision::query()
        ->whereKey($revision->id)
        ->toBase()
        ->update(['snapshot' => json_encode($snapshot)]);
    $component = livewire(TalentBookings::class, ['eventId' => $fixture['event']->id]);

    // Act
    $component->call('showHistory', 'wrestler', $fixture['person']->id);

    // Assert
    $component->assertSee('legacy_status')->assertDontSee('talent-bookings.states.legacy_status');
});

test('a pre-card referee can be proposed through the same private editor', function (): void {
    // Arrange
    $fixture = talentBookingFixture();
    $referee = Referee::factory()->create();
    $component = livewire(TalentBookings::class, ['eventId' => $fixture['event']->id]);

    // Act
    $component->set('searchKind', 'referees');
    $component->set('selectedPerson', $referee->id);
    $component->call('addPerson');
    $component->call('save');

    // Assert
    $component->assertHasNoErrors();
    expect(EventTalentBooking::query()->where('person_type', 'referee')->sole()->status)->toBe(TalentBookingStatus::Proposed);
});

test('unsupported editor identities fail closed without revealing a timeline', function (): void {
    // Arrange
    $fixture = talentBookingFixture();
    $component = livewire(TalentBookings::class, ['eventId' => $fixture['event']->id]);

    // Act / Assert
    $component->call('edit', 'tag_team', $fixture['person']->id)->assertNotFound();
});
