<?php

use App\Actions\Wrestlers\EmployAction;
use App\Actions\Wrestlers\UpdateAction;
use App\Data\WrestlerData;
use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;
use function Pest\Laravel\mock;

test('it updates a wrestler', function () {
    $data = new WrestlerData('Example Wrestler Name', 70, 220, 'Laraville, New York', null, null);
    $wrestler = Wrestler::factory()->create();

    mock(WrestlerRepository::class)
        ->shouldReceive('update')
        ->once()
        ->with($wrestler, $data)
        ->andReturns($wrestler);

    UpdateAction::run($wrestler, $data);
});

test('it employs an employable wrestler if start date is filled in request', function () {
    $datetime = now();
    $data = new WrestlerData('Example Wrestler Name', 70, 220, 'Laraville, New York', null, $datetime);
    $wrestler = Wrestler::factory()->create();

    mock(WrestlerRepository::class)
        ->shouldReceive('update')
        ->once()
        ->with($wrestler, $data)
        ->andReturns($wrestler);

    EmployAction::shouldRun($wrestler, $datetime);

    UpdateAction::run($wrestler, $data);
});

test('it updates a future employed wrestler employment date if start date is filled in request', function () {
    $datetime = now()->addDays(2);
    $data = new WrestlerData('Example Wrestler Name', 70, 220, 'Laraville, New York', null, $datetime);
    $wrestler = Wrestler::factory()->create();

    mock(WrestlerRepository::class)
        ->shouldReceive('update')
        ->once()
        ->with($wrestler, $data)
        ->andReturns($wrestler);

    EmployAction::shouldRun($wrestler, $datetime);

    UpdateAction::run($wrestler, $data);
});
