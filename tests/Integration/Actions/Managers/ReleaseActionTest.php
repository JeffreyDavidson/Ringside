<?php

declare(strict_types=1);

use App\Actions\Managers\ReleaseAction;
use App\Events\Managers\ManagerReleased;
use App\Exceptions\CannotBeReleasedException;
use App\Models\Manager;
use App\Repositories\ManagerRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    Event::fake();

    testTime()->freeze();

    $this->managerRepository = $this->mock(ManagerRepository::class);
});

test('it releases an available manager at the current datetime by default', function () {
    $manager = Manager::factory()->available()->create();
    $datetime = now();

    $this->managerRepository
        ->shouldReceive('reinstate');

    $this->managerRepository
        ->shouldNotReceive('clearInjury');

    $this->managerRepository
        ->shouldReceive('release')
        ->withArgs(function (Manager $releasableManager, Carbon $releaseDate) use ($manager, $datetime) {
            expect($releasableManager->is($manager))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->once()
        ->andReturn($manager);

    ReleaseAction::run($manager);

    Event::assertDispatched(ManagerReleased::class, function ($event) use ($manager, $datetime) {
        expect($event->manager->is($manager))->toBeTrue()
            ->and($event->releaseDate->eq($datetime))->toBeTrue();

        return true;
    });
});

test('it releases an available manager at a specific datetime', function () {
    $manager = Manager::factory()->available()->create();
    $datetime = now()->addDays(2);

    $this->managerRepository
        ->shouldReceive('reinstate');

    $this->managerRepository
        ->shouldNotReceive('clearInjury');

    $this->managerRepository
        ->shouldReceive('release')
        ->once()
        ->with($manager, $datetime)
        ->andReturn($manager);

    ReleaseAction::run($manager, $datetime);

    Event::assertDispatched(ManagerReleased::class, function ($event) use ($manager, $datetime) {
        expect($event->manager->is($manager))->toBeTrue()
            ->and($event->releaseDate->eq($datetime))->toBeTrue();

        return true;
    });
});

test('it releases a suspended manager at the current datetime by default', function () {
    $manager = Manager::factory()->suspended()->create();
    $datetime = now();

    $this->managerRepository
        ->shouldReceive('reinstate')
        ->once()
        ->withArgs(function (Manager $reinstatableManager, Carbon $releaseDate) use ($manager, $datetime) {
            expect($reinstatableManager->is($manager))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($manager);

    $this->managerRepository
        ->shouldNotReceive('clearInjury');

    $this->managerRepository
        ->shouldReceive('release')
        ->once()
        ->withArgs(function (Manager $releasableManager, Carbon $releaseDate) use ($manager, $datetime) {
            expect($releasableManager->is($manager))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($manager);

    ReleaseAction::run($manager);

    Event::assertDispatched(ManagerReleased::class, function ($event) use ($manager, $datetime) {
        expect($event->manager->is($manager))->toBeTrue()
            ->and($event->releaseDate->eq($datetime))->toBeTrue();

        return true;
    });
});

test('it releases a suspended manager at a specific datetime', function () {
    $manager = Manager::factory()->suspended()->create();
    $datetime = now()->addDays(2);

    $this->managerRepository
        ->shouldReceive('reinstate')
        ->once()
        ->with($manager, $datetime)
        ->andReturn($manager);

    $this->managerRepository
        ->shouldNotReceive('clearInjury');

    $this->managerRepository
        ->shouldReceive('release')
        ->once()
        ->with($manager, $datetime)
        ->andReturn($manager);

    ReleaseAction::run($manager, $datetime);

    Event::assertDispatched(ManagerReleased::class, function ($event) use ($manager, $datetime) {
        expect($event->manager->is($manager))->toBeTrue()
            ->and($event->releaseDate->eq($datetime))->toBeTrue();

        return true;
    });
});

test('it releases an injured manager at the current datetime by default', function () {
    $manager = Manager::factory()->injured()->create();
    $datetime = now();

    $this->managerRepository
        ->shouldReceive('reinstate');

    $this->managerRepository
        ->shouldReceive('clearInjury')
        ->once()
        ->withArgs(function (Manager $releasableManager, Carbon $releaseDate) use ($manager, $datetime) {
            expect($releasableManager->is($manager))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($manager);

    $this->managerRepository
        ->shouldReceive('release')
        ->once()
        ->withArgs(function (Manager $releasableManager, Carbon $releaseDate) use ($manager, $datetime) {
            expect($releasableManager->is($manager))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($manager);

    ReleaseAction::run($manager);

    Event::assertDispatched(ManagerReleased::class, function ($event) use ($manager, $datetime) {
        expect($event->manager->is($manager))->toBeTrue()
            ->and($event->releaseDate->eq($datetime))->toBeTrue();

        return true;
    });
});

test('it releases an injured manager at a specific datetime', function () {
    $manager = Manager::factory()->injured()->create();
    $datetime = now()->addDays(2);

    $this->managerRepository
        ->shouldReceive('reinstate');

    $this->managerRepository
        ->shouldReceive('clearInjury')
        ->once()
        ->with($manager, $datetime)
        ->andReturn($manager);

    $this->managerRepository
        ->shouldReceive('release')
        ->once()
        ->with($manager, $datetime)
        ->andReturn($manager);

    ReleaseAction::run($manager, $datetime);

    Event::assertDispatched(ManagerReleased::class, function ($event) use ($manager, $datetime) {
        expect($event->manager->is($manager))->toBeTrue()
            ->and($event->releaseDate->eq($datetime))->toBeTrue();

        return true;
    });
});

test('it throws an exception for releasing a non releasable manager', function ($factoryState) {
    $manager = Manager::factory()->{$factoryState}()->create();

    ReleaseAction::run($manager);
})->throws(CannotBeReleasedException::class)->with([
    'unemployed',
    'withFutureEmployment',
    'released',
    'retired',
]);
