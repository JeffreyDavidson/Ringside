<?php

declare(strict_types=1);

use App\Actions\Wrestlers\RestoreAction;
use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;

beforeEach(function () {
    $this->wrestlerRepository = $this->mock(WrestlerRepository::class);
});

test('it restores a deleted wrestler', function () {
    $wrestler = Wrestler::factory()->trashed()->create();

    $this->wrestlerRepository
        ->shouldReceive('restore')
        ->once()
        ->with($wrestler);

    RestoreAction::run($wrestler);
});
