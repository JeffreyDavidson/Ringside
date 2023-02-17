<?php

use App\Actions\Managers\RemoveFromCurrentWrestlersAction;
use App\Models\Manager;
use App\Repositories\ManagerRepository;
use function Pest\Laravel\mock;

test('it can remove current wrestlers from a manager', function () {
    $manager = Manager::factory()->create();

    mock(ManagerRepository::class)
        ->shouldReceive('removeFromCurrentWrestlers')
        ->once()
        ->with($manager);

    RemoveFromCurrentWrestlersAction::run($manager);
});
