<?php

declare(strict_types=1);

use App\Actions\Wrestlers\UpdateAction;
use App\Data\Wrestlers\WrestlerData;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\WrestlerRepository;

beforeEach(function () {
    $this->wrestlerRepository = $this->mock(WrestlerRepository::class);
});

test('it updates a wrestler', function () {
    $data = new WrestlerData('Example Wrestler Name', 70, 220, 'Laraville, New York', null, null);
    $wrestler = Wrestler::factory()->create();

    $this->wrestlerRepository
        ->shouldReceive('update')
        ->once()
        ->with($wrestler, $data)
        ->andReturns($wrestler);

    $this->wrestlerRepository
        ->shouldNotReceive('employ');

    resolve(UpdateAction::class)->handle($wrestler, $data);
});

test('it employs an employable wrestler if start date is filled in request', function () {
    $datetime = now();
    $data = new WrestlerData('Example Wrestler Name', 70, 220, 'Laraville, New York', null, $datetime);
    $wrestler = Wrestler::factory()->create();

    $this->wrestlerRepository
        ->shouldReceive('update')
        ->once()
        ->with($wrestler, $data)
        ->andReturns($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('createEmployment')
        ->with($wrestler, $data->employment_date)
        ->once()
        ->andReturn($wrestler);

    resolve(UpdateAction::class)->handle($wrestler, $data);
});

test('it updates a future employed wrestler employment date if start date is filled in request', function () {
    $datetime = now()->addDays(2);
    $data = new WrestlerData('Example Wrestler Name', 70, 220, 'Laraville, New York', null, $datetime);
    $wrestler = Wrestler::factory()->withFutureEmployment()->create();

    $this->wrestlerRepository
        ->shouldReceive('update')
        ->once()
        ->with($wrestler, $data)
        ->andReturns($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('createEmployment')
        ->with($wrestler, $data->employment_date)
        ->once()
        ->andReturn($wrestler);

    resolve(UpdateAction::class)->handle($wrestler, $data);
});
