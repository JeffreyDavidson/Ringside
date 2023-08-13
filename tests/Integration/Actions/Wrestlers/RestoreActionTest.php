<?php

use App\Actions\Wrestlers\RestoreAction;
use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;
use function Pest\Laravel\mock;

beforeEach(function () {
    $this->wrestlerRepository = mock(WrestlerRepository::class);
});

test('it restores a deleted wrestler', function () {
    $wrestler = Wrestler::factory()->trashed()->create();

    $this->wrestlerRepository
        ->shouldReceive('restore')
        ->once()
        ->with($wrestler);

    RestoreAction::run($wrestler);
});
