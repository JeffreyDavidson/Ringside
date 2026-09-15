<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Services\Events\EventDeletionService;

test('it soft deletes an event through the deletion state manager', function (): void {
    $event = Event::factory()->create();

    resolve(EventDeletionService::class)->delete($event, now());

    expect(Event::query()->find($event->id))->toBeNull()
        ->and(Event::withTrashed()->find($event->id))->not->toBeNull();
});

test('it restores an event when its venue remains available', function (): void {
    $date = now()->addWeek();
    $event = Event::factory()->create(['date' => $date]);
    resolve(EventDeletionService::class)->delete($event, now());

    resolve(EventDeletionService::class)->restore($event, now());

    expect(Event::query()->find($event->id))->not->toBeNull();
});
