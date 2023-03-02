<?php

use App\Actions\Wrestlers\RestoreAction;
use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;
use function Pest\Laravel\mock;

test('it restores a deleted wrestler', function () {
    $wrestler = Wrestler::factory()->trashed()->create();

    mock(WrestlerRepository::class)
        ->shouldReceive('restore')
        ->once()
        ->with($wrestler);

    RestoreAction::run($wrestler);
});
