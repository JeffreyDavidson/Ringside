<?php

use App\Actions\Wrestlers\DeleteAction;
use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;
use function Pest\Laravel\mock;

beforeEach(function () {
    $this->wrestlerRepository = mock(WrestlerRepository::class);
});

test('it deletes a wrestler', function () {
    $wrestler = Wrestler::factory()->create();

    $this->wrestlerRepository
        ->shouldReceive('delete')
        ->once()
        ->with($wrestler);

    DeleteAction::run($wrestler);
});
