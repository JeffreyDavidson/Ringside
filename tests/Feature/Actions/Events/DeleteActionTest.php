<?php

use App\Actions\Events\DeleteAction;
use App\Models\Event;
use App\Repositories\EventRepository;

test('deletes a event and redirects', function () {
    $event = Event::factory()->create();

    $this->mock(EventRepository::class)
        ->shouldReceive('delete')
        ->once()
        ->with($event)
        ->andReturns();

    DeleteAction::run($event);
});
