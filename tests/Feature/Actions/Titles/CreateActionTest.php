<?php

use App\Actions\Titles\ActivateAction;
use App\Actions\Titles\CreateAction;
use App\Data\TitleData;
use App\Models\Title;
use App\Repositories\TitleRepository;
use Illuminate\Support\Carbon;

test('it creates a title', function () {
    $data = new TitleData('Example Title', null);

    $this->mock(TitleRepository::class)
        ->shouldReceive('create')
        ->once()
        ->with($data)
        ->andReturns(new App\Models\Title());

    CreateAction::run($data);
});

test('it creates a title and activates it if activation date is provided', function () {
    $data = new TitleData('Example Title', Carbon::tomorrow());
    $title = Title::factory()->create(['name' => $data->name]);

    ActivateAction::shouldRun()->with($title, $data->activation_date)->once();

    $this->mock(TitleRepository::class)
        ->shouldReceive('create')
        ->once()
        ->with($data)
        ->andReturns($title);

    CreateAction::run($data);
});
