<?php

use App\Actions\Managers\RemoveFromCurrentWrestlersAction;
use App\Models\Manager;
use App\Repositories\ManagerRepository;
use function Pest\Laravel\mock;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Event::fake();

    $this->managerRepository = mock(ManagerRepository::class);
});

test('it can remove current wrestlers from a manager', function () {
    $manager = Manager::factory()->create();

    $this->managerRepository
        ->shouldReceive('removeFromCurrentWrestlers')
        ->once()
        ->with($manager);

    RemoveFromCurrentWrestlersAction::run($manager);
});
