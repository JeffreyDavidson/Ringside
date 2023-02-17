<?php

use App\Actions\Managers\DeleteAction;
use App\Models\Manager;
use App\Repositories\ManagerRepository;
use function Pest\Laravel\mock;

test('handle deletes a manager', function () {
    $manager = Manager::factory()->create();

    mock(ManagerRepository::class)
        ->shouldReceive('delete')
        ->once()
        ->with($manager);

    DeleteAction::run($manager);
});
