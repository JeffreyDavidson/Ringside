<?php

declare(strict_types=1);

use App\Actions\Wrestlers\DeleteAction;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\WrestlerRepository;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->wrestlerRepository = $this->mock(WrestlerRepository::class);
});

test('it deletes a wrestler', function () {
    $wrestler = Wrestler::factory()->create();

    $this->wrestlerRepository
        ->shouldReceive('removeFromCurrentManagers')
        ->once()
        ->with($wrestler, Mockery::type(Carbon::class));

    $this->wrestlerRepository
        ->shouldReceive('delete')
        ->once()
        ->with($wrestler);

    resolve(DeleteAction::class)->handle($wrestler);
});
