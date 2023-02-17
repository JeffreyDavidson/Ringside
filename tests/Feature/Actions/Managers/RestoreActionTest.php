<?php

use App\Actions\Managers\RestoreAction;
use App\Models\Manager;
use App\Repositories\ManagerRepository;
use function Pest\Laravel\mock;

test('handle restores a soft deleted manager', function () {
    $manager = Manager::factory()->trashed()->create();

    mock(ManagerRepository::class)
        ->shouldReceive('restore')
        ->once()
        ->with($manager);

    RestoreAction::run($manager);
});
