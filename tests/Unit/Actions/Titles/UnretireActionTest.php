<?php

declare(strict_types=1);

use App\Actions\Titles\UnretireAction;
use App\Exceptions\CannotBeUnretiredException;
use App\Models\Title;
use App\Repositories\TitleRepository;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->titleRepository = $this->mock(TitleRepository::class);
});

test('it unretires a retired title and redirects', function () {
    $title = Title::factory()->retired()->create();
    $datetime = now();

    $this->titleRepository
        ->shouldReceive('unretire')
        ->once()
        ->withArgs(function (Title $unretireTitle, Carbon $unretireDate) use ($title, $datetime) {
            expect($unretireTitle->is($title))->toBeTrue()
                ->and($unretireDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($title);

    $this->titleRepository
        ->shouldReceive('activate')
        ->once()
        ->withArgs(function (Title $employableTitle, Carbon $unretireDate) use ($title, $datetime) {
            expect($employableTitle->is($title))->toBeTrue()
                ->and($unretireDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($title);

    UnretireAction::run($title);
});

test('it unretires a retired title at a specific datetime', function () {
    $title = Title::factory()->retired()->create();
    $datetime = now()->addDays(2);

    $this->titleRepository
        ->shouldReceive('unretire')
        ->once()
        ->with($title, $datetime)
        ->andReturn($title);

    $this->titleRepository
        ->shouldReceive('activate')
        ->once()
        ->with($title, $datetime)
        ->andReturn($title);

    UnretireAction::run($title, $datetime);
});

test('it throws exception for unretiring a non unretirable title', function ($factoryState) {
    $title = Title::factory()->{$factoryState}()->create();

    UnretireAction::run($title);
})->throws(CannotBeUnretiredException::class)->with([
    'active',
    'inactive',
    'withFutureActivation',
    'unactivated',
]);
