<?php

use App\Actions\Events\UpdateAction;
use App\Data\EventData;
use App\Models\Event;
use App\Repositories\EventRepository;

test('it updates a event', function () {
    $data = new EventData('Example Event Name', null, null, null);
    $event = Event::factory()->create();

    $this->mock(EventRepository::class)
        ->shouldReceive('update')
        ->once()
        ->with($event, $data)
        ->andReturns($event);

    UpdateAction::run($event, $data);
});
