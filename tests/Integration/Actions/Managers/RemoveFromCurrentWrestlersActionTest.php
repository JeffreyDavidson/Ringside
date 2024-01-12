<?php

declare(strict_types=1);

use App\Actions\Managers\RemoveFromCurrentWrestlersAction;
use App\Models\Manager;
use App\Repositories\ManagerRepository;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Event::fake();

    $this->managerRepository = Mockery::mock(ManagerRepository::class);
});

test('it can remove current wrestlers from a manager', function () {
    $manager = Manager::factory()->create();

    $this->managerRepository
        ->shouldReceive('removeFromCurrentWrestlers')
        ->once()
        ->with($manager);

    RemoveFromCurrentWrestlersAction::run($manager);
});
