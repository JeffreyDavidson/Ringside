<?php

declare(strict_types=1);

use App\Actions\Managers\RemoveFromCurrentTagTeamsAction;
use App\Models\Manager;
use App\Repositories\ManagerRepository;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Event::fake();

    $this->managerRepository = Mockery::mock(ManagerRepository::class);
});

test('it can remove current tag teams from a manager', function () {
    $manager = Manager::factory()->create();

    $this->managerRepository
        ->shouldReceive('removeFromCurrentTagTeams')
        ->once()
        ->with($manager);

    RemoveFromCurrentTagTeamsAction::run($manager);
});
