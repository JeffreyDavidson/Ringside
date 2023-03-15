<?php

use App\Actions\Managers\RestoreAction;
use App\Models\Manager;
use App\Repositories\ManagerRepository;
use function Pest\Laravel\mock;

beforeEach(function () {
    $this->managerRepository = mock(ManagerRepository::class);
});

test('it restores a deleted manager', function () {
    $manager = Manager::factory()->trashed()->create();

    $this->managerRepository
        ->shouldReceive('restore')
        ->once()
        ->with($manager);

    RestoreAction::run($manager);
});
