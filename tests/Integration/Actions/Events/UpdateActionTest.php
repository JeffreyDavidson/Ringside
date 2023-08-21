<?php

use App\Actions\Events\UpdateAction;
use App\Data\EventData;
use App\Models\Event;
use App\Repositories\EventRepository;

use function Pest\Laravel\mock;

beforeEach(function () {
    $this->eventRepository = mock(EventRepository::class);
});

test('it updates a event', function () {
    $data = new EventData('Example Event Name', null, null, null);
    $event = Event::factory()->create();

    $this->eventRepository
        ->shouldReceive('update')
        ->once()
        ->with($event, $data)
        ->andReturns($event);

    UpdateAction::run($event, $data);
});
