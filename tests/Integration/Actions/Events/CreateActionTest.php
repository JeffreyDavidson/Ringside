<?php

use App\Actions\Events\CreateAction;
use App\Data\EventData;
use App\Repositories\EventRepository;

use function Pest\Laravel\mock;

beforeEach(function () {
    $this->eventRepository = mock(EventRepository::class);
});

test('it creates an event', function () {
    $data = new EventData('Example Event Name', null, null, null);

    $this->eventRepository
        ->shouldReceive('create')
        ->once()
        ->with($data)
        ->andReturns(new App\Models\Event());

    CreateAction::run($data);
});
