<?php

use App\Actions\Events\RestoreAction;
use App\Models\Event;
use App\Repositories\EventRepository;

use function Pest\Laravel\mock;

beforeEach(function () {
    $this->eventRepository = mock(EventRepository::class);
});

test('it restores a deleted event', function () {
    $event = Event::factory()->trashed()->create();

    $this->eventRepository
        ->shouldReceive('restore')
        ->once()
        ->with($event)
        ->andReturns();

    RestoreAction::run($event);
});
