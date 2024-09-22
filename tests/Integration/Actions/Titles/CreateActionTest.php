<?php

declare(strict_types=1);

use App\Actions\Titles\CreateAction;
use App\Data\TitleData;
use App\Models\Title;
use App\Repositories\TitleRepository;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->titleRepository = $this->mock(TitleRepository::class);
});

test('it creates a title', function () {
    $data = new TitleData('Example Title', null);

    $this->titleRepository
        ->shouldReceive('create')
        ->once()
        ->with($data)
        ->andReturns(new App\Models\Title);

    $this->titleRepository
        ->shouldNotReceive('activate');

    CreateAction::run($data);
});

test('it activates a title if activation date is filled in request', function () {
    $datetime = now();
    $data = new TitleData('Example Title', $datetime);
    $title = Title::factory()->create(['name' => $data->name]);

    $this->titleRepository
        ->shouldReceive('create')
        ->once()
        ->with($data)
        ->andReturn($title);

    $this->titleRepository
        ->shouldReceive('activate')
        ->once()
        ->with($title, $data->activation_date);

    CreateAction::run($data);
});
