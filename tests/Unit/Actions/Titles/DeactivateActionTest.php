<?php

declare(strict_types=1);

use App\Actions\Titles\DeactivateAction;
use App\Exceptions\CannotBeDeactivatedException;
use App\Models\Titles\Title;
use App\Repositories\TitleRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    Event::fake();

    testTime()->freeze();

    $this->titleRepository = $this->mock(TitleRepository::class);
});

test('it deactivates an active title at the current datetime by default', function () {
    $title = Title::factory()->active()->create();
    $datetime = now();

    $this->titleRepository
        ->shouldReceive('deactivate')
        ->once()
        ->withArgs(function (Title $deactivatableTitle, Carbon $deactivationDate) use ($title, $datetime) {
            expect($deactivatableTitle->is($title))->toBeTrue()
                ->and($deactivationDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($title);

    resolve(DeactivateAction::class)->handle($title);
});

test('it deactivates an active title at a specific datetime', function () {
    $title = Title::factory()->active()->create();
    $datetime = now();

    $this->titleRepository
        ->shouldReceive('deactivate')
        ->once()
        ->with($title, $datetime)
        ->andReturn($title);

    resolve(DeactivateAction::class)->handle($title, $datetime);
});

test('it throws exception for deactivating a non deactivatable title', function ($factoryState) {
    $title = Title::factory()->{$factoryState}()->create();

    resolve(DeactivateAction::class)->handle($title);
})->throws(CannotBeDeactivatedException::class)->with([
    'unactivated',
    'withFutureActivation',
    'inactive',
    'retired',
]);
