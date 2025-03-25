<?php

declare(strict_types=1);

use App\Actions\Managers\DeleteAction;
use App\Models\Manager;
use App\Repositories\ManagerRepository;

beforeEach(function () {
    $this->managerRepository = $this->mock(ManagerRepository::class);
});

test('it deletes a manager', function () {
    $manager = Manager::factory()->create();

    $this->managerRepository
        ->shouldReceive('delete')
        ->once()
        ->with($manager);

    DeleteAction::run($manager);
});
