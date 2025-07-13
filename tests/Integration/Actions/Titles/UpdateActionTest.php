<?php

declare(strict_types=1);

use App\Actions\Titles\UpdateAction;
use App\Data\Titles\TitleData;
use App\Enums\Titles\TitleType;
use App\Models\Titles\Title;
use App\Repositories\TitleRepository;

beforeEach(function () {
    $this->titleRepository = $this->mock(TitleRepository::class);
});

test('it updates a title', function () {
    $data = new TitleData('New Example Title', TitleType::Singles, null);
    $title = Title::factory()->create();

    $this->titleRepository
        ->shouldReceive('update')
        ->once()
        ->with($title, $data)
        ->andReturns($title);

    $this->titleRepository
        ->shouldNotReceive('createDebut');

    resolve(UpdateAction::class)->handle($title, $data);
});

test('it activates an unactivated title if activation date is filled in request', function () {
    $datetime = now();
    $data = new TitleData('New Example Title', TitleType::Singles, $datetime);
    $title = Title::factory()->unactivated()->create();

    $this->titleRepository
        ->shouldReceive('update')
        ->once()
        ->with($title, $data)
        ->andReturns($title);

    $this->titleRepository
        ->shouldReceive('createDebut')
        ->with($title, $data->debut_date)
        ->once()
        ->andReturn($title);

    resolve(UpdateAction::class)->handle($title, $data);
});

test('it updates a title with a future activated title activation date if activation date is filled in request', function () {
    $datetime = now()->addDays(2);
    $data = new TitleData('New Example Title', TitleType::Singles, $datetime);
    $title = Title::factory()->withFutureActivation()->create();

    $this->titleRepository
        ->shouldReceive('update')
        ->once()
        ->with($title, $data)
        ->andReturns($title);

    $this->titleRepository
        ->shouldReceive('createDebut')
        ->with($title, $data->debut_date)
        ->once()
        ->andReturn($title);

    resolve(UpdateAction::class)->handle($title, $data);
});
