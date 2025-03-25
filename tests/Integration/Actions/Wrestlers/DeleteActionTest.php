<?php

declare(strict_types=1);

use App\Actions\Wrestlers\DeleteAction;
use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;

beforeEach(function () {
    $this->wrestlerRepository = $this->mock(WrestlerRepository::class);
});

test('it deletes a wrestler', function () {
    $wrestler = Wrestler::factory()->create();

    $this->wrestlerRepository
        ->shouldReceive('delete')
        ->once()
        ->with($wrestler);

    DeleteAction::run($wrestler);
});
