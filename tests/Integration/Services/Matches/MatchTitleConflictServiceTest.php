<?php

declare(strict_types=1);

use App\Exceptions\Scheduling\SchedulingConflictException;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Titles\Title;
use App\Services\Matches\MatchTitleConflictService;

test('it rejects a title already assigned at another event time', function (): void {
    $eventDate = now()->addWeek();
    $event = Event::factory()->create(['date' => $eventDate]);
    $title = Title::factory()->active()->create();
    $match = EventMatch::factory()->forEvent($event)->create();
    $match->titles()->attach($title);

    expect(fn () => resolve(MatchTitleConflictService::class)
        ->ensureCanBeAssigned(collect([$event->id]), collect([$title])))
        ->toThrow(SchedulingConflictException::class, "Title [{$title->name}] is already assigned at this event time.");
});

test('it permits a title when no conflicting event has assigned it', function (): void {
    $event = Event::factory()->scheduled()->create();
    $title = Title::factory()->active()->create();

    expect(fn () => resolve(MatchTitleConflictService::class)
        ->ensureCanBeAssigned(collect([$event->id]), collect([$title])))
        ->not->toThrow(Throwable::class);
});
