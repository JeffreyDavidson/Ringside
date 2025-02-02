<?php

declare(strict_types=1);

use App\Actions\Titles\UpdateAction;
use App\Data\TitleData;
use App\Models\Title;
use App\Repositories\TitleRepository;

beforeEach(function () {
    $this->titleRepository = $this->mock(TitleRepository::class);
});

test('it updates a title', function () {
    $data = new TitleData('New Example Title', null);
    $title = Title::factory()->create();

    $this->titleRepository
        ->shouldReceive('update')
        ->once()
        ->with($title, $data)
        ->andReturns($title);

    $this->titleRepository
        ->shouldNotReceive('activate');

    UpdateAction::run($title, $data);
});

test('it activates an unactivated title if activation date is filled in request', function () {
    $datetime = now();
    $data = new TitleData('New Example Title', $datetime);
    $title = Title::factory()->unactivated()->create();

    $this->titleRepository
        ->shouldReceive('update')
        ->once()
        ->with($title, $data)
        ->andReturns($title);

    $this->titleRepository
        ->shouldReceive('activate')
        ->with($title, $data->activation_date)
        ->once()
        ->andReturn($title);

    UpdateAction::run($title, $data);
});

test('it updates a title with a future activated title activation date if activation date is filled in request', function () {
    $datetime = now()->addDays(2);
    $data = new TitleData('New Example Title', $datetime);
    $title = Title::factory()->withFutureActivation()->create();

    $this->titleRepository
        ->shouldReceive('update')
        ->once()
        ->with($title, $data)
        ->andReturns($title);

    $this->titleRepository
        ->shouldReceive('activate')
        ->with($title, $data->activation_date)
        ->once()
        ->andReturn($title);

    UpdateAction::run($title, $data);
})->skip();
