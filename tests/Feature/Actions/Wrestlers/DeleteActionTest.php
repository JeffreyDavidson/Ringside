<?php

use App\Actions\Wrestlers\DeleteAction;
use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;
use function Pest\Laravel\mock;

test('it deletes a wrestler', function () {
    $wrestler = Wrestler::factory()->create();

    mock(WrestlerRepository::class)
        ->shouldReceive('delete')
        ->once()
        ->with($wrestler);

    DeleteAction::run($wrestler);
});
