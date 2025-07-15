<?php

declare(strict_types=1);

use App\Actions\Managers\ReleaseAction;
use App\Events\Managers\ManagerReleased;
use App\Exceptions\Status\CannotBeReleasedException;
use App\Models\Managers\Manager;
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
        ->shouldReceive('createReinstatement');

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

    resolve(ReleaseAction::class)->handle($manager);

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
        ->shouldReceive('createReinstatement');

    $this->managerRepository
        ->shouldNotReceive('clearInjury');

    $this->managerRepository
        ->shouldReceive('release')
        ->once()
        ->with($manager, $datetime)
        ->andReturn($manager);

    resolve(ReleaseAction::class)->handle($manager, $datetime);

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
        ->shouldReceive('createReinstatement')
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

    resolve(ReleaseAction::class)->handle($manager);

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
        ->shouldReceive('createReinstatement')
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

    resolve(ReleaseAction::class)->handle($manager, $datetime);

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
        ->shouldReceive('createReinstatement');

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

    resolve(ReleaseAction::class)->handle($manager);

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
        ->shouldReceive('createReinstatement');

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

    resolve(ReleaseAction::class)->handle($manager, $datetime);

    Event::assertDispatched(ManagerReleased::class, function ($event) use ($manager, $datetime) {
        expect($event->manager->is($manager))->toBeTrue()
            ->and($event->releaseDate->eq($datetime))->toBeTrue();

        return true;
    });
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
