<?php

declare(strict_types=1);

use App\Actions\Managers\RemoveFromCurrentWrestlersAction;
use App\Models\Managers\Manager;
use App\Repositories\ManagerRepository;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Event::fake();

    $this->managerRepository = $this->mock(ManagerRepository::class);
});

test('it can remove current wrestlers from a manager', function () {
    $manager = Manager::factory()->create();
    $now = now();

    $this->managerRepository
        ->shouldReceive('removeFromCurrentWrestlers')
        ->once()
        ->with(
            Mockery::on(fn ($m) => $m->id === $manager->id),
            Mockery::on(fn ($d) => $d->eq($now))
        );

    resolve(RemoveFromCurrentWrestlersAction::class)->handle($manager, $now);
});
