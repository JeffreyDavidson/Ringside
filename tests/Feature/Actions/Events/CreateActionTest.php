<?php

use App\Actions\Events\CreateAction;
use App\Data\EventData;
use App\Repositories\EventRepository;

test('it creates an event', function () {
    $data = new EventData('Example Event Name', null, null, null);

    $this->mock(EventRepository::class)
        ->shouldReceive('create')
        ->once()
        ->with($data)
        ->andReturns(new App\Models\Event());

    CreateAction::run($data);
});
