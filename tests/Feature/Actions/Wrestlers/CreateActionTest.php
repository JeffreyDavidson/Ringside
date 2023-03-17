<?php

use App\Actions\Wrestlers\CreateAction;
use App\Data\WrestlerData;
use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;
use function Pest\Laravel\mock;
use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->wrestlerRepository = mock(WrestlerRepository::class);
});

test('it creates a wrestler', function () {
    $data = new WrestlerData('Example Wrestler Name', 70, 220, 'Laraville, New York', null, null);

    $this->wrestlerRepository
        ->shouldReceive('create')
        ->once()
        ->with($data)
        ->andReturns(new Wrestler());

    $this->wrestlerRepository
        ->shouldNotReceive('employ');

    CreateAction::run($data);
});

test('it employs a wrestler if start date is filled in request', function () {
    $dateTime = now();
    $data = new WrestlerData('Example Wrestler Name', 70, 220, 'Laraville, New York', null, $dateTime);
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
        ->shouldReceive('employ')
        ->once()
        ->with($wrestler, $data->start_date);

    CreateAction::run($data);
});
