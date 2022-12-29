<?php

use App\Actions\Events\RestoreAction;
use App\Models\Event;
use App\Repositories\EventRepository;

test('invoke restores a trashed event', function () {
    $event = Event::factory()->trashed()->create();

    $this->mock(EventRepository::class)
        ->shouldReceive('restore')
        ->once()
        ->with($event)
        ->andReturns();

    RestoreAction::run($event);
});
