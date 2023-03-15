<?php

use App\Actions\Titles\DeactivateAction;
use App\Exceptions\CannotBeDeactivatedException;
use App\Models\Title;
use App\Repositories\TitleRepository;
use function Pest\Laravel\mock;
use function Spatie\PestPluginTestTime\testTime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Event::fake();

    testTime()->freeze();

    $this->titleRepository = mock(TitleRepository::class);
});

test('it deactivates an active title at the current datetime by default', function () {
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
        ->andReturn($title);

    DeactivateAction::run($title);
});

test('it deactivates an active title at a specific datetime', function () {
    $title = Title::factory()->active()->create();
    $datetime = now();

    $this->titleRepository
        ->shouldReceive('deactivate')
        ->once()
        ->with($title, $datetime)
        ->andReturn($title);

    DeactivateAction::run($title, $datetime);
});

test('it throws exception for deactivating a non deactivatable title', function ($factoryState) {
    $title = Title::factory()->{$factoryState}()->create();

    DeactivateAction::run($title);
})->throws(CannotBeDeactivatedException::class)->with([
    'unactivated',
    'withFutureActivation',
    'inactive',
    'retired',
]);
