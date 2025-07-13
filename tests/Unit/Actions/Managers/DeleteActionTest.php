<?php

declare(strict_types=1);

use App\Actions\Managers\DeleteAction;
use App\Models\Managers\Manager;
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

    resolve(DeleteAction::class)->handle($manager);
});
