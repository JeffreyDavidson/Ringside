<?php

declare(strict_types=1);

use App\Actions\Managers\RemoveFromCurrentTagTeamsAction;
use App\Models\Managers\Manager;
use App\Repositories\ManagerRepository;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Event::fake();

    $this->managerRepository = $this->mock(ManagerRepository::class);
});

test('it can remove current tag teams from a manager', function () {
    $manager = Manager::factory()->create();
    $now = now();

    $this->managerRepository
        ->shouldReceive('removeFromCurrentTagTeams')
        ->once()
        ->with(
            Mockery::on(fn ($m) => $m->id === $manager->id),
            Mockery::on(fn ($d) => $d->eq($now))
        );

    resolve(RemoveFromCurrentTagTeamsAction::class)->handle($manager, $now);
});
