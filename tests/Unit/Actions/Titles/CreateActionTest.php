<?php

declare(strict_types=1);

use App\Actions\Titles\CreateAction;
use App\Data\Titles\TitleData;
use App\Enums\Titles\TitleType;
use App\Models\Titles\Title;
use App\Repositories\TitleRepository;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->titleRepository = $this->mock(TitleRepository::class);
});

test('it creates a title', function () {
    $data = new TitleData('Example Title', TitleType::Singles, null);

    $this->titleRepository
        ->shouldReceive('create')
        ->once()
        ->with($data)
        ->andReturns(new Title());

    $this->titleRepository
        ->shouldNotReceive('createDebut');

    resolve(CreateAction::class)->handle($data);
});

test('it activates a title if activation date is filled in request', function () {
    $datetime = now();
    $data = new TitleData('Example Title', TitleType::Singles, $datetime);
    $title = Title::factory()->create(['name' => $data->name]);

    $this->titleRepository
        ->shouldReceive('create')
        ->once()
        ->with($data)
        ->andReturn($title);

    $this->titleRepository
        ->shouldReceive('createDebut')
        ->once()
        ->with($title, $data->debut_date);

    resolve(CreateAction::class)->handle($data);
});
