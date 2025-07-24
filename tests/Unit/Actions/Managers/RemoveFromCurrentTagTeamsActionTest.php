<?php

declare(strict_types=1);

use App\Actions\Managers\RemoveFromCurrentTagTeamsAction;
use App\Models\Managers\Manager;
use App\Repositories\ManagerRepository;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->managerRepository = $this->mock(ManagerRepository::class);
});

test('it can remove current tag teams from a manager', function () {
    $manager = Manager::factory()->create();

    $this->managerRepository
        ->shouldReceive('removeFromCurrentTagTeams')
        ->once()
        ->with($manager, Mockery::type(Carbon::class));

    resolve(RemoveFromCurrentTagTeamsAction::class)->handle($manager);
});
