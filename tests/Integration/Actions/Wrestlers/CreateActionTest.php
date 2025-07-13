<?php

declare(strict_types=1);

use App\Actions\Wrestlers\CreateAction;
use App\Data\Wrestlers\WrestlerData;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\WrestlerRepository;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->wrestlerRepository = $this->mock(WrestlerRepository::class);
});

test('it creates a wrestler', function () {
    $data = new WrestlerData(
        'Example Wrestler Name',
        70,
        220,
        'New York City, New York',
        null,
        null
    );

    $this->wrestlerRepository
        ->shouldReceive('create')
        ->once()
        ->with($data)
        ->andReturns(new Wrestler());

    $this->wrestlerRepository
        ->shouldNotReceive('employ');

    resolve(CreateAction::class)->handle($data);
});

test('it employs a wrestler if start date is filled in request', function () {
    $dateTime = now();
    $data = new WrestlerData('Example Wrestler Name', 70, 220, 'New York City, New York', null, $dateTime);
    $wrestler = Wrestler::factory()->create([
        'name' => $data->name,
        'height' => $data->height,
        'weight' => 220,
        'hometown' => $data->hometown,
    ]);

    $this->wrestlerRepository
        ->shouldReceive('create')
        ->once()
        ->with($data)
        ->andReturn($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('createEmployment')
        ->once()
        ->with($wrestler, $data->employment_date);

    resolve(CreateAction::class)->handle($data);
});
