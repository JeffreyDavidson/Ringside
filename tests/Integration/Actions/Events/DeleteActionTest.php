<?php

declare(strict_types=1);

use App\Actions\Events\DeleteAction;
use App\Models\Events\Event;

test('it soft deletes an event through a locked record', function () {
    $event = Event::factory()->create();
    $staleEvent = clone $event;

    $event->refresh();
    resolve(DeleteAction::class)->handle($staleEvent);

    expect(Event::query()->find($event->getKey()))->toBeNull()
        ->and(Event::withTrashed()->find($event->getKey()))->not->toBeNull();
});
