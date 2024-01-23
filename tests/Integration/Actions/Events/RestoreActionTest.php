<?php

declare(strict_types=1);

use App\Actions\Events\RestoreAction;
use App\Models\Event;
use App\Repositories\EventRepository;

beforeEach(function () {
    $this->eventRepository = Mockery::mock(EventRepository::class);
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
