<?php

declare(strict_types=1);

use App\Actions\Events\DeleteAction;
use App\Models\Event;
use App\Repositories\EventRepository;

use function Pest\Laravel\mock;

beforeEach(function () {
    $this->eventRepository = mock(EventRepository::class);
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
