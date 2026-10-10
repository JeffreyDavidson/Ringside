<?php

declare(strict_types=1);

use App\Actions\Events\UpdateAction;
use App\Data\Events\EventData;
use App\Enums\Naming\GuardedName;
use App\Exceptions\Events\CannotBeRescheduledException;
use App\Exceptions\Events\NameTakenException;
use App\Exceptions\Scheduling\SchedulingConflictException;
use App\Lifecycle\Naming\RecordNameLock;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;

test('it rejects changing the date of an event that already occurred', function () {
    $originalDate = now()->subWeek();
    $event = Event::factory()->create(['date' => $originalDate]);
    $data = new EventData($event->name, now()->addWeek(), $event->venue, $event->preview);

    expect(fn () => resolve(UpdateAction::class)->handle($event, $data))
        ->toThrow(CannotBeRescheduledException::class)
        ->and($event->refresh()->date?->toDateTimeString())->toBe($originalDate->toDateTimeString());
});

test('it permits updating a past event when its date is unchanged', function () {
    $originalDate = now()->subWeek();
    $event = Event::factory()->create(['date' => $originalDate]);
    $data = new EventData('Updated Event Name', $originalDate->clone(), $event->venue, 'Updated preview');

    $updatedEvent = resolve(UpdateAction::class)->handle($event, $data);

    expect($updatedEvent)
        ->name->toBe('Updated Event Name')
        ->preview->toBe('Updated preview')
        ->and($updatedEvent->date?->toDateTimeString())->toBe($originalDate->toDateTimeString());
});

test('it rejects rescheduling when a wrestler is booked at the target time', function () {
    $originalDate = now()->addWeek();
    $targetDate = now()->addWeeks(2);
    $event = Event::factory()->create(['date' => $originalDate]);
    $conflictingEvent = Event::factory()->create(['date' => $targetDate]);
    $wrestler = Wrestler::factory()->bookable()->create();
    EventMatch::factory()->forEvent($event)->withCompetitors([$wrestler])->create();
    EventMatch::factory()->forEvent($conflictingEvent)->withCompetitors([$wrestler])->create();
    $data = new EventData('Rescheduled Event', $targetDate, null, null);

    expect(fn () => resolve(UpdateAction::class)->handle($event, $data))
        ->toThrow(SchedulingConflictException::class, "Wrestler [{$wrestler->name}] is already booked at this event time.")
        ->and($event->refresh()->date?->toDateTimeString())->toBe($originalDate->toDateTimeString());
});

test('it rejects rescheduling when a tag team is booked at the target time', function () {
    $originalDate = now()->addWeek();
    $targetDate = now()->addWeeks(2);
    $event = Event::factory()->create(['date' => $originalDate]);
    $conflictingEvent = Event::factory()->create(['date' => $targetDate]);
    $tagTeam = TagTeam::factory()->bookable()->create();
    EventMatch::factory()->forEvent($event)->withCompetitors([$tagTeam])->create();
    EventMatch::factory()->forEvent($conflictingEvent)->withCompetitors([$tagTeam])->create();
    $data = new EventData('Rescheduled Event', $targetDate, null, null);

    expect(fn () => resolve(UpdateAction::class)->handle($event, $data))
        ->toThrow(SchedulingConflictException::class, "Tag team [{$tagTeam->name}] is already booked at this event time.")
        ->and($event->refresh()->date?->toDateTimeString())->toBe($originalDate->toDateTimeString());
});

test('it rejects rescheduling when a referee is assigned at the target time', function () {
    $originalDate = now()->addWeek();
    $targetDate = now()->addWeeks(2);
    $event = Event::factory()->create(['date' => $originalDate]);
    $conflictingEvent = Event::factory()->create(['date' => $targetDate]);
    $referee = Referee::factory()->bookable()->create();
    $match = EventMatch::factory()->forEvent($event)->create();
    $conflictingMatch = EventMatch::factory()->forEvent($conflictingEvent)->create();
    $match->referees()->attach($referee);
    $conflictingMatch->referees()->attach($referee);
    $refereeName = $referee->refresh()->full_name;
    $data = new EventData('Rescheduled Event', $targetDate, null, null);

    expect(fn () => resolve(UpdateAction::class)->handle($event, $data))
        ->toThrow(SchedulingConflictException::class, "Referee [{$refereeName}] is already assigned to another event at this time.")
        ->and($event->refresh()->date?->toDateTimeString())->toBe($originalDate->toDateTimeString());
});

test('it rejects rescheduling when a title is assigned at the target time', function () {
    $originalDate = now()->addWeek();
    $targetDate = now()->addWeeks(2);
    $event = Event::factory()->create(['date' => $originalDate]);
    $conflictingEvent = Event::factory()->create(['date' => $targetDate]);
    $title = Title::factory()->active()->create();
    $match = EventMatch::factory()->forEvent($event)->create();
    $conflictingMatch = EventMatch::factory()->forEvent($conflictingEvent)->create();
    $match->titles()->attach($title);
    $conflictingMatch->titles()->attach($title);
    $data = new EventData('Rescheduled Event', $targetDate, null, null);

    expect(fn () => resolve(UpdateAction::class)->handle($event, $data))
        ->toThrow(SchedulingConflictException::class, "Title [{$title->name}] is already assigned at this event time.")
        ->and($event->refresh()->date?->toDateTimeString())->toBe($originalDate->toDateTimeString());
});

test('it rejects moving or unscheduling an event whose matches created or closed a reign', function (bool $closesReign, ?int $targetWeeks): void {
    // Arrange
    $originalDate = now()->addWeek();
    $event = Event::factory()->create(['date' => $originalDate]);
    $match = EventMatch::factory()->forEvent($event)->create();
    $reign = TitleChampionship::factory()->for(Title::factory()->active());

    $reign = $closesReign
        ? $reign->lostAtEventMatch($match)
        : $reign->wonAtEventMatch($match);
    $reign->create();
    $data = new EventData($event->name, $targetWeeks === null ? null : now()->addWeeks($targetWeeks), null, null);

    // Act
    $update = fn () => resolve(UpdateAction::class)->handle($event, $data);

    // Assert
    expect($update)->toThrow(
        CannotBeRescheduledException::class,
        "Event [{$event->name}] cannot be rescheduled because its matches have created or ended title reigns.",
    )
        ->and($event->refresh()->date?->toDateTimeString())->toBe($originalDate->toDateTimeString());
})->with([
    'moved after creating a reign' => [false, 3],
    'unscheduled after creating a reign' => [false, null],
    'moved after closing a reign' => [true, 3],
]);

test('it still updates other details of an event with title reigns when its date is unchanged', function (): void {
    // Arrange
    $originalDate = now()->addWeek();
    $event = Event::factory()->create(['date' => $originalDate]);
    $match = EventMatch::factory()->forEvent($event)->create();
    TitleChampionship::factory()->for(Title::factory()->active())->wonAtEventMatch($match)->create();

    // Act
    $updated = resolve(UpdateAction::class)->handle($event, new EventData('Renamed Event', $originalDate->copy(), null, null));

    // Assert
    expect($updated->name)->toBe('Renamed Event');
});

test('it reschedules an event whose reigns were voided', function (): void {
    // Arrange
    $event = Event::factory()->create(['date' => now()->addWeek()]);
    $match = EventMatch::factory()->forEvent($event)->create();
    TitleChampionship::factory()->for(Title::factory()->active())->wonAtEventMatch($match)->create()->delete();
    $targetDate = now()->addWeeks(3);

    // Act
    $updated = resolve(UpdateAction::class)->handle($event, new EventData($event->name, $targetDate, null, null));

    // Assert
    expect($updated->date?->toDateTimeString())->toBe($targetDate->toDateTimeString());
});

describe('event name guard', function (): void {
    test('it locks the new name of the promotion before the slot locks and the event row', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $event = Event::factory()->for($promotion, 'promotion')->create(['name' => 'Original Event', 'date' => now()->addWeeks(2)]);
        $data = new EventData(' Renamed Event ', now()->addWeek(), null, null);
        $lock = resolve(RecordNameLock::class);

        // Act
        $statements = recordStatements(fn () => resolve(UpdateAction::class)->handle($event, $data));

        // Assert
        $nameLock = statementPosition($statements, fn (array $statement): bool => str_starts_with($statement['sql'], 'insert into "record_name_locks"'));
        $firstSlotLock = statementPosition($statements, fn (array $statement): bool => str_contains($statement['sql'], 'scheduling_slot_locks'));
        $eventLock = statementPosition($statements, fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "events"'));

        expect($statements[$nameLock]['bindings'])->toBe([$lock->key(GuardedName::EventName, $promotion->id, 'Renamed Event')])
            ->and($nameLock)->toBeLessThan($firstSlotLock)
            ->and($firstSlotLock)->toBeLessThan($eventLock)
            ->and($event->refresh()->name)->toBe('Renamed Event');
    });

    test('it rejects a name another event of the same promotion already uses, even with surrounding space or deleted', function (bool $inPromotion, string $name, bool $deleted): void {
        // Arrange
        $promotion = $inPromotion ? Promotion::factory()->create() : null;
        $other = Event::factory()->state(['promotion_id' => $promotion?->id, 'name' => 'Summer Slam'])->create();
        $event = Event::factory()->state(['promotion_id' => $promotion?->id, 'name' => 'Original Event'])->create();

        if ($deleted) {
            $other->delete();
        }

        // Act
        $update = fn () => resolve(UpdateAction::class)->handle($event, new EventData($name, $event->date, null, null));

        // Assert
        expect($update)->toThrow(NameTakenException::class, "An event named 'Summer Slam' already exists in this promotion.")
            ->and($event->refresh()->name)->toBe('Original Event');
    })->with([
        'without a promotion' => [false, 'Summer Slam', false],
        'in a promotion' => [true, 'Summer Slam', false],
        'leading space' => [true, ' Summer Slam', false],
        'deleted' => [true, 'Summer Slam', true],
    ]);

    test('it keeps the name of the event being updated and allows one only another promotion uses', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        Event::factory()->for(Promotion::factory(), 'promotion')->create(['name' => 'Other Event']);
        $event = Event::factory()->for($promotion, 'promotion')->create(['name' => 'Same Event']);

        // Act
        resolve(UpdateAction::class)->handle($event, new EventData('Same Event', $event->date, null, 'Edited preview'));
        resolve(UpdateAction::class)->handle($event, new EventData('Other Event', $event->date, null, 'Edited preview'));

        // Assert
        expect($event->refresh()->name)->toBe('Other Event')
            ->and($event->preview)->toBe('Edited preview');
    });
});
