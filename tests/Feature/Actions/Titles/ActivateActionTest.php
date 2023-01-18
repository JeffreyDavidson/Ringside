<?php

use App\Actions\Titles\ActivateAction;
use App\Data\TitleData;
use App\Models\Title;
use App\Repositories\TitleRepository;
use Illuminate\Support\Carbon;

test('invoke calls activate action', function () {
    $data = new TitleData('Example Name Title', Carbon::tomorrow());
    $title = Title::factory()->unactivated()->create();

    $this->mock(TitleRepository::class)
        ->shouldReceive('activate')
        ->once()
        ->with($title, $data->activation_date);

    ActivateAction::run($title, $data->activation_date);
});

test('invoke activates a future activated title', function () {
    $data = new TitleData('Example Name Title', Carbon::tomorrow());
    $title = Title::factory()->withFutureActivation()->create();

    $this->mock(TitleRepository::class)
        ->shouldReceive('activate')
        ->once()
        ->with($title, $data->activation_date);

    ActivateAction::run($title, $data->activation_date);
});

test('invoke throws exception for unretiring a non unretirable title', function ($factoryState) {
    $this->withoutExceptionHandling();
    $data = new TitleData('Example Name Title', Carbon::tomorrow());
    $title = Title::factory()->{$factoryState}()->create();

    ActivateAction::run($title, $data->activation_date);
})->throws(CannotBeActivatedException::class)->with([
    'active',
    'retired',
]);
