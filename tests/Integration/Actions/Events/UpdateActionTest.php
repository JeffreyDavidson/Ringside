<?php

declare(strict_types=1);

use App\Actions\Events\UpdateAction;
use App\Data\EventData;
use App\Models\Event;
use App\Repositories\EventRepository;

beforeEach(function () {
    $this->eventRepository = $this->mock(EventRepository::class);
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
