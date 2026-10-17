<?php

declare(strict_types=1);

require_once __DIR__.'/../../../Fixtures/talent-booking.php';

use App\Actions\Events\SaveTalentBookingAction;
use App\Data\Events\TalentBookingData;
use App\Enums\Events\TalentBookingStatus;
use App\Enums\Matches\MatchType;
use App\Models\Events\Event;
use App\Models\Events\EventTalentBooking;
use App\Models\Events\EventTalentBookingRevision;
use App\Models\Events\Venue;
use App\Models\Matches\EventMatch;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Events\TalentBookingFingerprintService;
use App\ViewModels\Events\TalentBookingsViewModel;

use function Pest\Laravel\actingAs;

test('the checklist keeps unavailable agreements and flags event context changes', function (): void {
    // Arrange
    actingAs(administrator());
    $event = Event::factory()->create(['date' => now()->addDay()]);
    $people = Wrestler::factory()->count(2)->bookable()->create();
    EventMatch::factory()->for($event)->withCompetitors($people->all())->withReferees()->create();
    $person = $people->firstOrFail();
    $context = resolve(TalentBookingFingerprintService::class);
    $booking = resolve(SaveTalentBookingAction::class)->handle($event, 'wrestler', $person->id, 0,
        $context->fingerprint($event, "wrestler:{$person->id}"), new TalentBookingData(TalentBookingStatus::Confirmed, 0, null, 'UTC', null, true));
    $person->delete();
    $event->update(['date' => now()->addDays(2)]);

    // Act
    $data = resolve(TalentBookingsViewModel::class)->data($event);

    // Assert
    $row = collect($data['rows'])->firstOrFail(fn (array $row): bool => $row['key'] === "wrestler:{$person->id}");
    expect($row['warnings'])->toContain('unavailable', 'reconfirmation')
        ->and($row['booking']?->status)->toBe(TalentBookingStatus::Confirmed)
        ->and($data['counts']['attention'])->toBeGreaterThan(0)
        ->and($booking->refresh()->version)->toBe(1);
});

test('only material event context changes need reconfirmation', function (string $change, bool $review): void {
    // Arrange
    $fixture = talentBookingFixture();
    $event = $fixture['event'];
    $venue = $event->venue()->firstOrFail();

    // Act
    match ($change) {
        'event name' => $event->update(['name' => 'Renamed show']),
        'venue name' => $venue->update(['name' => 'Renamed venue']),
        'date' => $event->update(['date' => now()->addDays(2)]),
        'venue' => $event->update(['venue_id' => Venue::factory()->create()->id]),
        'address' => $venue->update(['street_address' => 'New address']),
        'unset venue' => $event->update(['venue_id' => null]),
        'deleted venue' => $venue->delete(),
        default => throw new InvalidArgumentException('Unknown context change'),
    };
    $data = resolve(TalentBookingsViewModel::class)->data($event->refresh());

    // Assert
    expect(in_array('reconfirmation', $data['rows'][0]['warnings'], true))->toBe($review)
        ->and($fixture['booking']->refresh()->revisions()->count())->toBe(1);
})->with(['rename event' => ['event name', false], 'rename venue' => ['venue name', false], 'date' => ['date', true],
    'venue' => ['venue', true], 'address' => ['address', true], 'missing venue' => ['unset venue', true], 'deleted venue' => ['deleted venue', true]]);

test('returning to agreed context clears the live warning without a new agreement', function (): void {
    // Arrange
    $fixture = talentBookingFixture();
    $date = $fixture['event']->date?->copy();
    $fixture['event']->update(['date' => now()->addDays(3)]);

    // Act
    $fixture['event']->update(['date' => $date]);
    $data = resolve(TalentBookingsViewModel::class)->data($fixture['event']);

    // Assert
    expect($data['rows'][0]['warnings'])->toBeEmpty()->and($data['counts']['confirmed_clear'])->toBe(1);
});

test('tag team members have individual rows and agreements never transfer to replacements', function (): void {
    // Arrange
    actingAs(administrator());
    $event = Event::factory()->create(['date' => now()->addDay()]);
    $members = Wrestler::factory()->count(2)->bookable()->create();
    $team = TagTeam::factory()->bookable()->withCurrentWrestlers($members)->create();
    $opponent = TagTeam::factory()->bookable()->withCurrentWrestlers(Wrestler::factory()->count(2)->bookable()->create())->create();
    EventMatch::factory()->for($event)->withCompetitors([$team, $opponent])->withReferees()->create(['match_type' => MatchType::TagTeam]);
    $person = $members->firstOrFail();
    resolve(SaveTalentBookingAction::class)->handle($event, 'wrestler', $person->id, 0,
        resolve(TalentBookingFingerprintService::class)->fingerprint($event, "wrestler:{$person->id}"), new TalentBookingData(TalentBookingStatus::Confirmed, 5000, null, 'UTC', null, true));
    $team->wrestlers()->updateExistingPivot($person->id, ['left_at' => now()]);
    $replacement = Wrestler::factory()->bookable()->create();
    $team->wrestlers()->attach($replacement, ['joined_at' => now()]);

    // Act
    $data = resolve(TalentBookingsViewModel::class)->data($event);

    // Assert
    expect(collect($data['rows'])->firstOrFail(fn (array $row): bool => $row['key'] === "wrestler:{$person->id}")['warnings'])->toContain('removed')
        ->and(collect($data['rows'])->firstOrFail(fn (array $row): bool => $row['key'] === "wrestler:{$replacement->id}")['booking'])->toBeNull()
        ->and(collect($data['rows'])->filter(fn (array $row): bool => $row['type'] === 'referee'))->toHaveCount(1);
});

test('a cancelled person still on the card requires attention without losing terms', function (): void {
    // Arrange
    $fixture = talentBookingFixture();
    $opponent = Wrestler::factory()->bookable()->create();
    EventMatch::factory()->for($fixture['event'])->withCompetitors([$fixture['person'], $opponent])->withReferees()->create();
    saveTalentFixture($fixture, new TalentBookingData(TalentBookingStatus::Cancelled, null, null, 'UTC', null, false, 'Cancelled'));

    // Act
    $row = collect(resolve(TalentBookingsViewModel::class)->data($fixture['event'])['rows'])
        ->firstOrFail(fn (array $row): bool => $row['key'] === "wrestler:{$fixture['person']->id}");

    // Assert
    expect($row['warnings'])->toContain('cancelled_card')->and($row['booking']?->fee_minor)->toBe(123456);
});

test('fees are formatted with a sign, grouping and exact cents, and an unknown fee stays distinct from zero', function (?int $minor, string $label): void {
    // Arrange
    $fixture = talentBookingFixture();
    $booking = $fixture['booking'];
    $booking->confirmed_snapshot = [...($booking->confirmed_snapshot ?? throw new LogicException('Confirmed fixture required')),
        'terms' => [...$booking->confirmed_snapshot['terms'], 'fee_minor' => $minor]];
    $revision = EventTalentBookingRevision::factory()->make(['snapshot' => ['status' => 'confirmed', 'fee_minor' => $minor]]);
    $revision->id = 77;

    // Act
    $data = resolve(TalentBookingsViewModel::class)->data($fixture['event'], $booking, [$revision]);

    // Assert
    expect($data['agreedFee'])->toBe($label)
        ->and($data['historyFees'])->toBe([77 => $label]);
})->with([
    'unknown' => [null, 'USD Not recorded'],
    'zero' => [0, 'USD 0.00'],
    'positive' => [123456, 'USD 1,234.56'],
    'large' => [9999999999, 'USD 99,999,999.99'],
    'negative' => [-150, 'USD -1.50'],
    'negative under a dollar' => [-5, 'USD -0.05'],
    'negative with grouping' => [-123456, 'USD -1,234.56'],
]);

test('the roster lists everyone on the card and everyone booked, sorted by name, with whether they are on the card and bookable', function (): void {
    // Arrange
    actingAs(administrator());
    $event = Event::factory()
        ->create(['date' => now()->addDay()]);
    $zed = Wrestler::factory()
        ->bookable()
        ->create(['name' => 'zed Last']);
    $amy = Wrestler::factory()
        ->bookable()
        ->create(['name' => 'Amy First']);
    $injured = Wrestler::factory()
        ->unemployed()
        ->create(['name' => 'Mid Unavailable']);
    $bookedOnly = Wrestler::factory()
        ->bookable()
        ->create(['name' => 'Bea Booked Only']);
    EventMatch::factory()
        ->for($event)
        ->withCompetitors([$zed, $amy, $injured])
        ->withReferees()
        ->create();
    $booking = EventTalentBooking::factory()
        ->for($event)
        ->create(['person_type' => 'wrestler', 'person_id' => $bookedOnly->id, 'person_name' => 'Bea Booked Only', 'person_role' => 'Wrestler']);

    // Act
    $data = resolve(TalentBookingsViewModel::class)->data($event);

    // Assert
    $rowOf = fn (Wrestler $wrestler): array => collect($data['rows'])->firstOrFail(fn (array $row): bool => $row['key'] === "wrestler:{$wrestler->id}");
    expect($data['rows'])->toHaveCount(5)
        ->and(collect($data['rows'])->where('type', 'wrestler')->pluck('name')->all())->toBe(['Amy First', 'Bea Booked Only', 'Mid Unavailable', 'zed Last'])
        ->and($rowOf($amy)['on_card'])->toBeTrue()
        ->and($rowOf($amy)['unavailable'])->toBeFalse()
        ->and($rowOf($amy)['matches'])->toBe([1])
        ->and($rowOf($amy)['booking'])->toBeNull()
        ->and($rowOf($injured)['unavailable'])->toBeTrue()
        ->and($rowOf($bookedOnly)['on_card'])->toBeFalse()
        ->and($rowOf($bookedOnly)['booking']?->is($booking))->toBeTrue()
        ->and($data['card_warning'])->toBeTrue();
});

test('the roster reads the same statements for a bigger card', function (): void {
    // Arrange
    actingAs(administrator());
    $event = Event::factory()
        ->create(['date' => now()->addDay()]);
    EventMatch::factory()
        ->for($event)
        ->withCompetitors(Wrestler::factory()->bookable()->count(2)->create()->all())
        ->create();
    $viewModel = resolve(TalentBookingsViewModel::class);
    $small = recordStatements(fn () => $viewModel->data($event));
    EventMatch::factory()
        ->for($event)
        ->count(3)
        ->withCompetitors(Wrestler::factory()->bookable()->count(2)->create()->all())
        ->create();

    // Act
    $large = recordStatements(fn () => $viewModel->data($event));

    // Assert
    expect($large)->toHaveSameSize($small);
});

test('no booking and no history leave the fee labels empty', function (): void {
    // Arrange
    $fixture = talentBookingFixture();

    // Act
    $data = resolve(TalentBookingsViewModel::class)->data($fixture['event']);

    // Assert
    expect($data['agreedFee'])->toBeNull()
        ->and($data['historyFees'])->toBeEmpty();
});
