<?php

use App\Actions\Titles\RetireAction;
use App\Exceptions\CannotBeRetiredException;
use App\Models\Title;
use App\Repositories\TitleRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use function Pest\Laravel\mock;
use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    Event::fake();

    testTime()->freeze();

    $this->titleRepository = mock(TitleRepository::class);
});

test('it retires an active title at the current datetime by default', function () {
    $title = Title::factory()->active()->create();
    $datetime = now();

    $this->titleRepository
        ->shouldReceive('deactivate')
        ->once()
        ->withArgs(function (Title $deactivatableTitle, Carbon $deactivationDate) use ($title, $datetime) {
            expect($deactivatableTitle->is($title))->toBeTrue();
            expect($deactivationDate->equalTo($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($title);

    $this->titleRepository
        ->shouldReceive('retire')
        ->once()
        ->withArgs(function (Title $retirableTitle, Carbon $retirementDate) use ($title, $datetime) {
            expect($retirableTitle->is($title))->toBeTrue();
            expect($retirementDate->equalTo($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($title);

    RetireAction::run($title);
});

test('it retires an active title at a specific datetime', function () {
    $title = Title::factory()->active()->create();
    $datetime = now()->addDays(2);

    $this->titleRepository
        ->shouldReceive('deactivate')
        ->once()
        ->with($title, $datetime)
        ->andReturns($title);

    $this->titleRepository
        ->shouldReceive('retire')
        ->once()
        ->with($title, $datetime)
        ->andReturns($title);

    RetireAction::run($title, $datetime);
});

test('it retires an inactive title at the current datetime by default', function () {
    $title = Title::factory()->inactive()->create();
    $datetime = now();

    $this->titleRepository
        ->shouldNotReceive('deactivate');

    $this->titleRepository
        ->shouldReceive('retire')
        ->once()
        ->withArgs(function (Title $retirableTitle, Carbon $retirementDate) use ($title, $datetime) {
            expect($retirableTitle->is($title))->toBeTrue();
            expect($retirementDate->equalTo($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($title);

    RetireAction::run($title);
});

test('it retires an inactive title at a specific datetime', function () {
    $title = Title::factory()->inactive()->create();
    $datetime = now()->addDays(2);

    $this->titleRepository
        ->shouldNotReceive('deactivate');

    $this->titleRepository
        ->shouldReceive('retire')
        ->once()
        ->with($title, $datetime)
        ->andReturns($title);

    RetireAction::run($title, $datetime);
});

test('invoke throws exception for unretiring a non unretirable title', function ($factoryState) {
    $title = Title::factory()->{$factoryState}()->create();

    RetireAction::run($title);
})->throws(CannotBeRetiredException::class)->with([
    'retired',
    'withFutureActivation',
    'unactivated',
]);
