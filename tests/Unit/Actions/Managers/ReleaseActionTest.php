<?php

declare(strict_types=1);

use App\Actions\Managers\ReleaseAction;
use App\Exceptions\Status\CannotBeReleasedException;
use App\Models\Managers\Manager;
use App\Repositories\ManagerRepository;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->managerRepository = $this->mock(ManagerRepository::class);
});

test('it releases an employed manager at the current datetime by default', function () {
    $manager = Manager::factory()->employed()->create();
    $datetime = now();

    $this->managerRepository
        ->shouldReceive('endEmployment')
        ->withArgs(function (Manager $releasableManager, Carbon $releaseDate) use ($manager, $datetime) {
            expect($releasableManager->is($manager))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->once();

    resolve(ReleaseAction::class)->handle($manager);

    // No event is dispatched by the ReleaseAction
});

test('it releases an employed manager at a specific datetime', function () {
    $manager = Manager::factory()->employed()->create();
    $datetime = now()->addDays(2);

    $this->managerRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->with($manager, $datetime);

    resolve(ReleaseAction::class)->handle($manager, $datetime);

    // No event is dispatched by the ReleaseAction
});

test('it releases a suspended manager at the current datetime by default', function () {
    $manager = Manager::factory()->suspended()->create();
    $datetime = now();

    $this->managerRepository
        ->shouldReceive('endSuspension')
        ->once()
        ->withArgs(function (Manager $suspendedManager, Carbon $releaseDate) use ($manager, $datetime) {
            expect($suspendedManager->is($manager))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        });

    $this->managerRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->withArgs(function (Manager $releasableManager, Carbon $releaseDate) use ($manager, $datetime) {
            expect($releasableManager->is($manager))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        });

    resolve(ReleaseAction::class)->handle($manager);

    // No event is dispatched by the ReleaseAction
});

test('it releases a suspended manager at a specific datetime', function () {
    $manager = Manager::factory()->suspended()->create();
    $datetime = now()->addDays(2);

    $this->managerRepository
        ->shouldReceive('endSuspension')
        ->once()
        ->with($manager, $datetime);

    $this->managerRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->with($manager, $datetime);

    resolve(ReleaseAction::class)->handle($manager, $datetime);

    // No event is dispatched by the ReleaseAction
});

test('it releases an injured manager at the current datetime by default', function () {
    $manager = Manager::factory()->injured()->create();
    $datetime = now();

    $this->managerRepository
        ->shouldReceive('endInjury')
        ->once()
        ->withArgs(function (Manager $injuredManager, Carbon $releaseDate) use ($manager, $datetime) {
            expect($injuredManager->is($manager))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        });

    $this->managerRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->withArgs(function (Manager $releasableManager, Carbon $releaseDate) use ($manager, $datetime) {
            expect($releasableManager->is($manager))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        });

    resolve(ReleaseAction::class)->handle($manager);

    // No event is dispatched by the ReleaseAction
});

test('it releases an injured manager at a specific datetime', function () {
    $manager = Manager::factory()->injured()->create();
    $datetime = now()->addDays(2);

    $this->managerRepository
        ->shouldReceive('endInjury')
        ->once()
        ->with($manager, $datetime);

    $this->managerRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->with($manager, $datetime);

    resolve(ReleaseAction::class)->handle($manager, $datetime);

    // No event is dispatched by the ReleaseAction
});

test('it throws an exception for releasing a non releasable manager', function ($factoryState) {
    $manager = Manager::factory()->{$factoryState}()->create();

    resolve(ReleaseAction::class)->handle($manager);
})->throws(CannotBeReleasedException::class)->with([
    'unemployed',
    'withFutureEmployment',
    'released',
    'retired',
]);
