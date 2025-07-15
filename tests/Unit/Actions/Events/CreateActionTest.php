<?php

declare(strict_types=1);

use App\Actions\Events\CreateAction;
use App\Data\Events\EventData;
use App\Repositories\EventRepository;

beforeEach(function () {
    $this->eventRepository = $this->mock(EventRepository::class);
});

test('it creates an event', function () {
    $data = new EventData('Example Event Name', null, null, null);

    $this->eventRepository
        ->shouldReceive('create')
        ->once()
        ->with($data)
        ->andReturns(new App\Models\Events\Event());

    resolve(CreateAction::class)->handle($data);
});
