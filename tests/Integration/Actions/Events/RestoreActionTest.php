<?php

declare(strict_types=1);

use App\Actions\Events\DeleteAction;
use App\Actions\Events\RestoreAction;
use App\Models\Events\Event;

test('it restores a soft-deleted event', function (): void {
    $event = Event::factory()->scheduled()->withVenue()->create();
    resolve(DeleteAction::class)->handle($event);

    $deletedEvent = Event::withTrashed()->findOrFail($event->id);
    resolve(RestoreAction::class)->handle($deletedEvent);

    expect(Event::query()->find($event->id))->not->toBeNull()
        ->and(Event::withTrashed()->findOrFail($event->id)->deleted_at)->toBeNull();
});
