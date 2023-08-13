<?php

use App\Actions\Managers\DeleteAction;
use App\Models\Manager;
use App\Repositories\ManagerRepository;
use function Pest\Laravel\mock;

beforeEach(function () {
    $this->managerRepository = mock(ManagerRepository::class);
});

test('it deletes a manager', function () {
    $manager = Manager::factory()->create();

    $this->managerRepository
        ->shouldReceive('delete')
        ->once()
        ->with($manager);

    DeleteAction::run($manager);
});
