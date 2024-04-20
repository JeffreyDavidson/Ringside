<?php

declare(strict_types=1);

use App\Actions\Managers\RestoreAction;
use App\Models\Manager;
use App\Repositories\ManagerRepository;

beforeEach(function () {
    $this->managerRepository = $this->mock(ManagerRepository::class);
});

test('it restores a deleted manager', function () {
    $manager = Manager::factory()->trashed()->create();

    $this->managerRepository
        ->shouldReceive('restore')
        ->once()
        ->with($manager);

    RestoreAction::run($manager);
});
