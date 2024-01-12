<?php

declare(strict_types=1);

use App\Actions\Events\DeleteAction;
use App\Models\Event;
use App\Repositories\EventRepository;

beforeEach(function () {
    $this->eventRepository = Mockery::mock(EventRepository::class);
});

test('it deletes a event', function () {
    $event = Event::factory()->create();

    $this->eventRepository
        ->shouldReceive('delete')
        ->once()
        ->with($event)
        ->andReturns();

    DeleteAction::run($event);
});
