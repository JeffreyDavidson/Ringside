<?php

declare(strict_types=1);

require_once __DIR__.'/../../../Fixtures/talent-booking.php';

use App\Actions\Events\DeleteAction;
use App\Actions\Events\RestoreAction;
use App\Actions\Events\SaveTalentBookingAction;
use App\Data\Events\TalentBookingData;
use App\Enums\Events\TalentBookingStatus;
use App\Exceptions\Events\TalentBookingException;
use App\Exceptions\RecordChangedException;
use App\Models\Events\Event;
use App\Models\Events\EventTalentBooking;
use App\Models\Events\EventTalentBookingRevision;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Events\TalentBookingFingerprintService;
use App\ViewModels\Events\TalentBookingsViewModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use function Pest\Laravel\actingAs;

test('confirmation stores one individual agreement and durable private history', function (): void {
    // Arrange
    actingAs(administrator());
    $event = Event::factory()->create(['date' => now()->addDay()]);
    $person = Wrestler::factory()->bookable()->create();
    $fingerprint = resolve(TalentBookingFingerprintService::class)->fingerprint($event, "wrestler:{$person->id}");

    // Act
    $booking = resolve(SaveTalentBookingAction::class)->handle($event, 'wrestler', $person->id, 0, $fingerprint,
        new TalentBookingData(TalentBookingStatus::Confirmed, 125050, now(), 'UTC', 'Private travel', true), signedInUser());

    // Assert
    expect($booking->status)->toBe(TalentBookingStatus::Confirmed)
        ->and($booking->fee_minor)->toBe(125050)->and($booking->currency)->toBe('USD')
        ->and($booking->version)->toBe(1)->and($booking->confirmed_snapshot['terms']['travel_notes'] ?? null)->toBe('Private travel')
        ->and($booking->revisions()->sole()->snapshot['fee_minor'])->toBe(125050)
        ->and($booking->revisions()->sole()->actor_id)->toBe(auth()->id());
    expect(EventTalentBooking::query()->count())->toBe(1);
});

test('stale first creation and opposite transitions never overwrite or duplicate history', function (): void {
    // Arrange
    actingAs(administrator());
    $event = Event::factory()->create(['date' => now()->addDay()]);
    $person = Wrestler::factory()->bookable()->create();
    $fingerprint = resolve(TalentBookingFingerprintService::class)->fingerprint($event, "wrestler:{$person->id}");
    $action = resolve(SaveTalentBookingAction::class);
    $data = new TalentBookingData(TalentBookingStatus::Confirmed, null, null, 'UTC', null, true);
    $action->handle($event, 'wrestler', $person->id, 0, $fingerprint, $data, signedInUser());

    // Act / Assert
    expect(fn () => $action->handle($event, 'wrestler', $person->id, 0, $fingerprint, $data, signedInUser()))->toThrow(RecordChangedException::class);
    expect(fn () => $action->handle($event, 'wrestler', $person->id, 0, $fingerprint,
        new TalentBookingData(TalentBookingStatus::Cancelled, null, null, 'UTC', null, false, 'Cancelled'), signedInUser()))->toThrow(RecordChangedException::class)
        ->and(EventTalentBooking::query()->sole()->status)->toBe(TalentBookingStatus::Confirmed)
        ->and(EventTalentBookingRevision::query()->count())->toBe(1);
});

test('cancellation and reopening retain terms and every earlier agreement', function (): void {
    // Arrange
    $fixture = talentBookingFixture();
    $confirmed = $fixture['booking']->confirmed_snapshot;

    // Act
    $cancelled = saveTalentFixture($fixture, new TalentBookingData(TalentBookingStatus::Cancelled, null, null, 'UTC', null, false, 'LEAK-REASON-7Q'));
    $reopened = saveTalentFixture($fixture, new TalentBookingData(TalentBookingStatus::Proposed, null, null, 'UTC', null));

    // Assert
    expect($cancelled->fee_minor)->toBe(123456)->and($reopened->fee_minor)->toBe(123456)
        ->and($reopened->travel_notes)->toBe('LEAK-TRAVEL-7Q')->and($reopened->confirmed_snapshot)->toBe($confirmed)
        ->and($reopened->status)->toBe(TalentBookingStatus::Proposed)->and($reopened->revisions()->count())->toBe(3);
});

test('changed terms stay pending until a fresh agreement is recorded', function (): void {
    // Arrange
    $fixture = talentBookingFixture();

    // Act
    $pending = saveTalentFixture($fixture, new TalentBookingData(TalentBookingStatus::Confirmed, 0, null, 'UTC', 'Changed', false));

    // Assert
    expect($pending->terms_need_review)->toBeTrue()->and($pending->confirmed_snapshot['terms']['fee_minor'] ?? null)->toBe(123456);

    // Act
    $agreed = saveTalentFixture($fixture, new TalentBookingData(TalentBookingStatus::Confirmed, 0, null, 'UTC', 'Changed', true));

    // Assert
    expect($agreed->terms_need_review)->toBeFalse()->and($agreed->confirmed_snapshot['terms']['fee_minor'] ?? null)->toBe(0)
        ->and($agreed->revisions()->count())->toBe(3);
});

test('invalid transitions are refused without new history', function (string $case): void {
    // Arrange
    $fixture = talentBookingFixture();
    $data = match ($case) {
        'cancel reason' => new TalentBookingData(TalentBookingStatus::Cancelled, null, null, 'UTC', null),
        'correction reason' => new TalentBookingData(TalentBookingStatus::Contacted, null, null, 'UTC', null),
        'negative fee' => new TalentBookingData(TalentBookingStatus::Proposed, -1, null, 'UTC', null, false, 'Correction'),
        default => new TalentBookingData(TalentBookingStatus::Confirmed, null, null, 'UTC', null, true),
    };
    if ($case === 'unscheduled') {
        $fixture['event']->update(['date' => null]);
    }
    if ($case === 'cancelled') {
        saveTalentFixture($fixture, new TalentBookingData(TalentBookingStatus::Cancelled, null, null, 'UTC', null, false, 'Cancelled'));
    }
    $count = EventTalentBookingRevision::query()->count();

    // Act / Assert
    expect(fn () => saveTalentFixture($fixture, $data))->toThrow(TalentBookingException::class);
    expect(EventTalentBookingRevision::query()->count())->toBe($count);
})->with(['cancel reason', 'correction reason', 'negative fee', 'unscheduled', 'cancelled']);

test('an unchanged save adds no revision and requires no invented contact step', function (): void {
    // Arrange
    $fixture = talentBookingFixture();

    // Act
    $booking = saveTalentFixture($fixture, new TalentBookingData(TalentBookingStatus::Confirmed, 123456,
        $fixture['booking']->arrival_at, 'UTC', 'LEAK-TRAVEL-7Q'));

    // Assert
    expect($booking->version)->toBe(1)->and($booking->revisions()->count())->toBe(1);
});

test('a changed event refuses a stale reconfirmation without overwriting it', function (): void {
    // Arrange
    $fixture = talentBookingFixture();
    $fingerprint = resolve(TalentBookingFingerprintService::class)->fingerprint($fixture['event'], "wrestler:{$fixture['person']->id}");
    $fixture['event']->update(['date' => now()->addDays(2)]);

    // Act / Assert
    expect(fn () => resolve(SaveTalentBookingAction::class)->handle($fixture['event'], 'wrestler', $fixture['person']->id, 1, $fingerprint,
        new TalentBookingData(TalentBookingStatus::Confirmed, null, null, 'UTC', null, true), signedInUser()))->toThrow(RecordChangedException::class);
    expect($fixture['booking']->refresh()->version)->toBe(1);
});

test('unknown people, foreign promotions and team identities cannot become individual agreements', function (string $kind): void {
    // Arrange
    $fixture = talentBookingFixture();
    $id = 999999;
    $type = 'wrestler';
    if ($kind === 'foreign') {
        $id = Wrestler::factory()->for(Promotion::factory())->create()->id;
    }
    if ($kind === 'team') {
        $type = 'tag_team';
        $id = $fixture['person']->id;
    }

    // Act / Assert
    expect(fn () => resolve(SaveTalentBookingAction::class)->handle($fixture['event'], $type, $id, 0, '',
        new TalentBookingData(TalentBookingStatus::Proposed, null, null, 'UTC', null), signedInUser()))->toThrow(TalentBookingException::class);
    expect(EventTalentBooking::query()->count())->toBe(1);
})->with(['unknown', 'foreign', 'team']);

test('soft deleted events cannot accept writes and restored events retain agreements for review', function (): void {
    // Arrange
    $fixture = talentBookingFixture();
    resolve(DeleteAction::class)->handle($fixture['event']);

    // Act / Assert
    expect(fn () => saveTalentFixture($fixture, new TalentBookingData(TalentBookingStatus::Proposed, null, null, 'UTC', null)))
        ->toThrow(ModelNotFoundException::class);
    resolve(RestoreAction::class)->handle($fixture['event']);
    $row = resolve(TalentBookingsViewModel::class)->data($fixture['event'])['rows'][0];
    expect($row['warnings'])->toContain('reconfirmation')->and($fixture['booking']->refresh()->version)->toBe(1);
});

test('booking revisions cannot be rewritten or deleted', function (): void {
    // Arrange
    $fixture = talentBookingFixture();
    $revision = $fixture['booking']->revisions()->sole();

    // Act / Assert
    expect(fn () => $revision->forceFill(['reason' => 'Rewrite'])->save())->toThrow(LogicException::class);
    expect(fn () => $revision->delete())->toThrow(LogicException::class)
        ->and($revision->refresh()->reason)->toBeNull();
});

test('the save locks the owning event before children and writes both records atomically', function (): void {
    // Arrange
    $fixture = talentBookingFixture();

    // Act
    $statements = recordStatements(fn () => saveTalentFixture($fixture,
        new TalentBookingData(TalentBookingStatus::Cancelled, null, null, 'UTC', null, false, 'Cancel')));

    // Assert
    $eventLock = statementPosition($statements, fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "events"'));
    $bookingLock = statementPosition($statements, fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "event_talent_bookings"'));
    $write = statementPosition($statements, fn (array $statement): bool => str_starts_with($statement['sql'], 'update "event_talent_bookings"'));
    expect($eventLock)->toBeLessThan($bookingLock)->and($bookingLock)->toBeLessThan($write);
});

test('a referee agreement remains distinct from a wrestler with the same numeric identity', function (): void {
    // Arrange
    $fixture = talentBookingFixture();
    $referee = Referee::factory()->bookable()->create();
    $context = resolve(TalentBookingFingerprintService::class);

    // Act
    $booking = resolve(SaveTalentBookingAction::class)->handle($fixture['event'], 'referee', $referee->id, 0,
        $context->fingerprint($fixture['event'], "referee:{$referee->id}"), new TalentBookingData(TalentBookingStatus::Confirmed, 7500, null, 'UTC', null, true), signedInUser());

    // Assert
    expect($booking->person_type)->toBe('referee')->and($booking->person_name)->toBe($referee->refresh()->full_name)
        ->and($booking->fee_minor)->toBe(7500)->and(EventTalentBooking::query()->count())->toBe(2);
});

/** Save a status move on a booking that is already in `$from`. */
function saveTalentMove(TalentBookingStatus $from, TalentBookingStatus $to, ?string $reason): Closure
{
    actingAs(administrator());
    $event = Event::factory()->create(['date' => now()->addDay()]);
    $person = Wrestler::factory()->bookable()->create();
    EventTalentBooking::factory()->create(['event_id' => $event->id, 'person_id' => $person->id, 'status' => $from]);

    return fn (): EventTalentBooking => resolve(SaveTalentBookingAction::class)->handle($event, 'wrestler', $person->id, 1,
        resolve(TalentBookingFingerprintService::class)->fingerprint($event, "wrestler:{$person->id}"),
        new TalentBookingData($to, null, null, 'UTC', null, true, $reason), signedInUser())->refresh();
}

test('a booking moves without a reason when the rules do not ask for one', function (TalentBookingStatus $from, TalentBookingStatus $to): void {
    // Arrange
    $save = saveTalentMove($from, $to, null);

    // Act
    $booking = $save();

    // Assert
    expect($booking->status)->toBe($to);
})->with([
    'proposed to proposed' => [TalentBookingStatus::Proposed, TalentBookingStatus::Proposed],
    'proposed to contacted' => [TalentBookingStatus::Proposed, TalentBookingStatus::Contacted],
    'proposed to confirmed' => [TalentBookingStatus::Proposed, TalentBookingStatus::Confirmed],
    'contacted to proposed' => [TalentBookingStatus::Contacted, TalentBookingStatus::Proposed],
    'contacted to contacted' => [TalentBookingStatus::Contacted, TalentBookingStatus::Contacted],
    'contacted to confirmed' => [TalentBookingStatus::Contacted, TalentBookingStatus::Confirmed],
    'confirmed to confirmed' => [TalentBookingStatus::Confirmed, TalentBookingStatus::Confirmed],
    'cancelled to proposed' => [TalentBookingStatus::Cancelled, TalentBookingStatus::Proposed],
]);

test('a booking moves when it is given a reason', function (TalentBookingStatus $from, TalentBookingStatus $to): void {
    // Arrange
    $save = saveTalentMove($from, $to, 'Agreed with the talent');

    // Act
    $booking = $save();

    // Assert
    expect($booking->status)->toBe($to);
})->with([
    'proposed to proposed' => [TalentBookingStatus::Proposed, TalentBookingStatus::Proposed],
    'proposed to contacted' => [TalentBookingStatus::Proposed, TalentBookingStatus::Contacted],
    'proposed to confirmed' => [TalentBookingStatus::Proposed, TalentBookingStatus::Confirmed],
    'contacted to proposed' => [TalentBookingStatus::Contacted, TalentBookingStatus::Proposed],
    'contacted to contacted' => [TalentBookingStatus::Contacted, TalentBookingStatus::Contacted],
    'contacted to confirmed' => [TalentBookingStatus::Contacted, TalentBookingStatus::Confirmed],
    'confirmed to proposed' => [TalentBookingStatus::Confirmed, TalentBookingStatus::Proposed],
    'confirmed to contacted' => [TalentBookingStatus::Confirmed, TalentBookingStatus::Contacted],
    'confirmed to confirmed' => [TalentBookingStatus::Confirmed, TalentBookingStatus::Confirmed],
    'cancelled to proposed' => [TalentBookingStatus::Cancelled, TalentBookingStatus::Proposed],
    'proposed to cancelled' => [TalentBookingStatus::Proposed, TalentBookingStatus::Cancelled],
    'contacted to cancelled' => [TalentBookingStatus::Contacted, TalentBookingStatus::Cancelled],
    'confirmed to cancelled' => [TalentBookingStatus::Confirmed, TalentBookingStatus::Cancelled],
]);

test('a cancellation or a correction of a confirmation is refused without a reason', function (TalentBookingStatus $from, TalentBookingStatus $to): void {
    // Arrange
    $save = saveTalentMove($from, $to, null);

    // Act
    $attempt = $save;

    // Assert
    expect($attempt)->toThrow(TalentBookingException::class, __('talent-bookings.errors.reason'))
        ->and(EventTalentBooking::query()->sole()->status)->toBe($from);
})->with([
    'proposed to cancelled' => [TalentBookingStatus::Proposed, TalentBookingStatus::Cancelled],
    'contacted to cancelled' => [TalentBookingStatus::Contacted, TalentBookingStatus::Cancelled],
    'confirmed to cancelled' => [TalentBookingStatus::Confirmed, TalentBookingStatus::Cancelled],
    'confirmed to proposed' => [TalentBookingStatus::Confirmed, TalentBookingStatus::Proposed],
    'confirmed to contacted' => [TalentBookingStatus::Confirmed, TalentBookingStatus::Contacted],
]);

test('a cancelled booking can only be reopened as proposed', function (TalentBookingStatus $to, ?string $reason): void {
    // Arrange
    $save = saveTalentMove(TalentBookingStatus::Cancelled, $to, $reason);

    // Act
    $attempt = $save;

    // Assert
    expect($attempt)->toThrow(TalentBookingException::class, __('talent-bookings.errors.reopen'))
        ->and(EventTalentBooking::query()->sole()->status)->toBe(TalentBookingStatus::Cancelled);
})->with([
    'contacted' => [TalentBookingStatus::Contacted],
    'confirmed' => [TalentBookingStatus::Confirmed],
    'cancelled' => [TalentBookingStatus::Cancelled],
])->with(['no reason' => null, 'with a reason' => 'Agreed with the talent']);

test('a booking is only confirmed once the agreement is recorded', function (): void {
    // Arrange
    actingAs(administrator());
    $event = Event::factory()->create(['date' => now()->addDay()]);
    $person = Wrestler::factory()->bookable()->create();
    $booking = EventTalentBooking::factory()->create(['event_id' => $event->id, 'person_id' => $person->id, 'status' => TalentBookingStatus::Contacted]);

    // Act
    $confirm = fn () => resolve(SaveTalentBookingAction::class)->handle($event, 'wrestler', $person->id, 1,
        resolve(TalentBookingFingerprintService::class)->fingerprint($event, "wrestler:{$person->id}"),
        new TalentBookingData(TalentBookingStatus::Confirmed, null, null, 'UTC', null), signedInUser());

    // Assert
    expect($confirm)->toThrow(TalentBookingException::class, __('talent-bookings.errors.confirm'))
        ->and($booking->refresh()->status)->toBe(TalentBookingStatus::Contacted);
});

test('a booking revision records the actor passed in, not the signed-in user', function (): void {
    // Arrange
    actingAs(administrator());
    $actor = administrator();
    $event = Event::factory()->create(['date' => now()->addDay()]);
    $person = Wrestler::factory()->bookable()->create();
    $fingerprint = resolve(TalentBookingFingerprintService::class)->fingerprint($event, "wrestler:{$person->id}");

    // Act
    $booking = resolve(SaveTalentBookingAction::class)->handle($event, 'wrestler', $person->id, 0, $fingerprint,
        new TalentBookingData(TalentBookingStatus::Confirmed, 125050, now(), 'UTC', 'Private travel', true), $actor);

    // Assert
    $revision = EventTalentBookingRevision::query()->where('booking_id', $booking->id)->sole();
    expect($revision->actor_id)->toBe($actor->id)
        ->and($revision->actor_name)->toBe("{$actor->first_name} {$actor->last_name}");
});
