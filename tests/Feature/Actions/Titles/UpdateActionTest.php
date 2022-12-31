<?php

use App\Actions\Titles\ActivateAction;
use App\Actions\Titles\UpdateAction;
use App\Data\TitleData;
use App\Models\Title;
use App\Repositories\TitleRepository;

test('it updates a title', function () {
    $data = new TitleData('New Example Title', null);
    $title = Title::factory()->create();

    $this->mock(TitleRepository::class)
        ->shouldReceive('update')
        ->once()
        ->with($title, $data)
        ->andReturns($title);

    UpdateAction::run($title, $data);
});

test('it activates an unactivated title when activation date is filled', function () {
    $data = new TitleData('Example Name Title', now());
    $title = Title::factory()->unactivated()->create();

    ActivateAction::shouldRun()->with($title, $data->activation_date)->once();

    $this->mock(TitleRepository::class)
        ->shouldReceive('update')
        ->once()
        ->with($title, $data)
        ->andReturns($title);

    UpdateAction::run($title, $data);
});
