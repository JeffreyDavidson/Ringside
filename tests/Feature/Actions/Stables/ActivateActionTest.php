<?php

use App\Actions\Stables\ActivateAction;
use App\Exceptions\CannotBeActivatedException;
use App\Models\Stable;
use App\Repositories\StableRepository;
use Illuminate\Support\Carbon;
use function Pest\Laravel\mock;
use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    Event::fake();

    testTime()->freeze();

    $this->stableRepository = mock(StableRepository::class);
});

test('invoke activates an unactivated stable and employs its unemployed members at the current datetime by default', function () {
    $stable = Stable::factory()->unactivated()->withUnemployedDefaultMembers()->create();
    $datetime = now();

    $this->stableRepository
        ->shouldReceive('activate')
        ->once()
        ->withArgs(function (Stable $activatableStable, Carbon $employmentDate) use ($stable, $datetime) {
            expect($activatableStable->is($stable))->toBeTrue();
            expect($employmentDate->equalTo($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($stable);

    ActivateAction::run($stable);
});

test('invoke activates a future activated stable with members at a specific datetime', function () {
    $stable = Stable::factory()->withFutureActivation()->create();
    $datetime = now()->addDays(2);

    $this->stableRepository
        ->shouldReceive('activate')
        ->once()
        ->with($stable, $datetime)
        ->andReturns($stable);

    ActivateAction::run($stable);
});

test('invoke throws exception for activating a non activatable stable', function ($factoryState) {
    $stable = Stable::factory()->{$factoryState}()->create();

    ActivateAction::run($stable);
})->throws(CannotBeActivatedException::class)->with([
    'active',
]);
