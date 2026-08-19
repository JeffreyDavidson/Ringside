<?php

declare(strict_types=1);

use App\Actions\Events\UpdateAction;
use App\Data\Events\EventData;
use App\Exceptions\Scheduling\SchedulingConflictException;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;

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
        ->toThrow(SchedulingConflictException::class, "Wrestler [{$wrestler->name}] is already booked at this event time.");

    expect($event->refresh()->date?->toDateTimeString())->toBe($originalDate->toDateTimeString());
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
        ->toThrow(SchedulingConflictException::class, "Tag team [{$tagTeam->name}] is already booked at this event time.");

    expect($event->refresh()->date?->toDateTimeString())->toBe($originalDate->toDateTimeString());
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
        ->toThrow(SchedulingConflictException::class, "Referee [{$refereeName}] is already assigned to another event at this time.");

    expect($event->refresh()->date?->toDateTimeString())->toBe($originalDate->toDateTimeString());
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
        ->toThrow(SchedulingConflictException::class, "Title [{$title->name}] is already assigned at this event time.");

    expect($event->refresh()->date?->toDateTimeString())->toBe($originalDate->toDateTimeString());
});
