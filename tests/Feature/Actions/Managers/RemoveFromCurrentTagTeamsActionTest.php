<?php

use App\Actions\Managers\RemoveFromCurrentTagTeamsAction;
use App\Models\Manager;
use App\Repositories\ManagerRepository;
use function Pest\Laravel\mock;

test('it can remove current tag teams from a manager', function () {
    $manager = Manager::factory()->create();

    mock(ManagerRepository::class)
        ->shouldReceive('removeFromCurrentTagTeams')
        ->once()
        ->with($manager);

    RemoveFromCurrentTagTeamsAction::run($manager);
});
