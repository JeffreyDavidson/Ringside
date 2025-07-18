<?php

declare(strict_types=1);

use App\Actions\Managers\RetireAction;
use App\Events\Managers\ManagerRetired;
use App\Exceptions\Status\CannotBeRetiredException;
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

test('it retires an employed manager at the current datetime by default', function () {
    $manager = Manager::factory()->employed()->create();
    $datetime = now();

    $this->managerRepository
        ->shouldNotReceive('reinstate');

    $this->managerRepository
        ->shouldNotReceive('clearInjury');

    $this->managerRepository
        ->shouldReceive('release')
        ->once()
        ->withArgs(function (Manager $releasableManager, Carbon $retirementDate) use ($manager, $datetime) {
            expect($releasableManager->is($manager))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($manager);

    $this->managerRepository
        ->shouldReceive('retire')
        ->once()
        ->withArgs(function (Manager $retirableManager, Carbon $retirementDate) use ($manager, $datetime) {
            expect($retirableManager->is($manager))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($manager);

    resolve(RetireAction::class)->handle($manager);

    Event::assertDispatched(ManagerRetired::class, function ($event) use ($manager, $datetime) {
        expect($event->manager->is($manager))->toBeTrue()
            ->and($event->retirementDate->eq($datetime))->toBeTrue();

        return true;
    });
});

test('it retires an employed manager at a specific datetime', function () {
    $manager = Manager::factory()->employed()->create();
    $datetime = now()->addDays(2);

    $this->managerRepository
        ->shouldNotReceive('reinstate')
        ->shouldNotReceive('clearInjury');

    $this->managerRepository
        ->shouldReceive('release')
        ->once()
        ->with($manager, $datetime)
        ->andReturns($manager);

    $this->managerRepository
        ->shouldReceive('retire')
        ->once()
        ->with($manager, $datetime)
        ->andReturns($manager);

    resolve(RetireAction::class)->handle($manager, $datetime);

    Event::assertDispatched(ManagerRetired::class, function ($event) use ($manager, $datetime) {
        expect($event->manager->is($manager))->toBeTrue()
            ->and($event->retirementDate->eq($datetime))->toBeTrue();

        return true;
    });
});

test('it retires a suspended manager at the current datetime by default', function () {
    $manager = Manager::factory()->suspended()->create();
    $datetime = now();

    $this->managerRepository
        ->shouldReceive('createReinstatement')
        ->once()
        ->withArgs(function (Manager $reinstatableManager, Carbon $retirementDate) use ($manager, $datetime) {
            expect($reinstatableManager->is($manager))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($manager);

    $this->managerRepository
        ->shouldNotReceive('clearInjury');

    $this->managerRepository
        ->shouldReceive('release')
        ->once()
        ->withArgs(function (Manager $releasableManager, Carbon $retirementDate) use ($manager, $datetime) {
            expect($releasableManager->is($manager))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($manager);

    $this->managerRepository
        ->shouldReceive('retire')
        ->once()
        ->withArgs(function (Manager $retirableManager, Carbon $retirementDate) use ($manager, $datetime) {
            expect($retirableManager->is($manager))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($manager);

    resolve(RetireAction::class)->handle($manager);

    Event::assertDispatched(ManagerRetired::class, function ($event) use ($manager, $datetime) {
        expect($event->manager->is($manager))->toBeTrue()
            ->and($event->retirementDate->eq($datetime))->toBeTrue();

        return true;
    });
});

test('it retires a suspended manager at a specific datetime', function () {
    $manager = Manager::factory()->suspended()->create();
    $datetime = now()->addDays(2);

    $this->managerRepository
        ->shouldReceive('createReinstatement')
        ->once()
        ->with($manager, $datetime)
        ->andReturns($manager);

    $this->managerRepository
        ->shouldNotReceive('clearInjury');

    $this->managerRepository
        ->shouldReceive('release')
        ->once()
        ->with($manager, $datetime)
        ->andReturns($manager);

    $this->managerRepository
        ->shouldReceive('retire')
        ->once()
        ->with($manager, $datetime)
        ->andReturns($manager);

    resolve(RetireAction::class)->handle($manager, $datetime);

    Event::assertDispatched(ManagerRetired::class, function ($event) use ($manager, $datetime) {
        expect($event->manager->is($manager))->toBeTrue()
            ->and($event->retirementDate->eq($datetime))->toBeTrue();

        return true;
    });
});

test('it retires an injured manager at the current datetime by default', function () {
    $manager = Manager::factory()->injured()->create();
    $datetime = now();

    $this->managerRepository
        ->shouldNotReceive('reinstate');

    $this->managerRepository
        ->shouldReceive('clearInjury')
        ->once()
        ->withArgs(function (Manager $clearableManager, Carbon $retirementDate) use ($manager, $datetime) {
            expect($clearableManager->is($manager))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($manager);

    $this->managerRepository
        ->shouldReceive('release')
        ->once()
        ->withArgs(function (Manager $releasableManager, Carbon $retirementDate) use ($manager, $datetime) {
            expect($releasableManager->is($manager))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($manager);

    $this->managerRepository
        ->shouldReceive('retire')
        ->once()
        ->withArgs(function (Manager $retirableManager, Carbon $retirementDate) use ($manager, $datetime) {
            expect($retirableManager->is($manager))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($manager);

    resolve(RetireAction::class)->handle($manager);

    Event::assertDispatched(ManagerRetired::class, function ($event) use ($manager, $datetime) {
        expect($event->manager->is($manager))->toBeTrue()
            ->and($event->retirementDate->eq($datetime))->toBeTrue();

        return true;
    });
});

test('it retires an injured manager at a specific datetime', function () {
    $manager = Manager::factory()->injured()->create();
    $datetime = now()->addDays(2);

    $this->managerRepository
        ->shouldNotReceive('reinstate');

    $this->managerRepository
        ->shouldReceive('clearInjury')
        ->once()
        ->with($manager, $datetime)
        ->andReturns($manager);

    $this->managerRepository
        ->shouldReceive('release')
        ->once()
        ->with($manager, $datetime)
        ->andReturns($manager);

    $this->managerRepository
        ->shouldReceive('retire')
        ->once()
        ->with($manager, $datetime)
        ->andReturns($manager);

    resolve(RetireAction::class)->handle($manager, $datetime);

    Event::assertDispatched(ManagerRetired::class, function ($event) use ($manager, $datetime) {
        expect($event->manager->is($manager))->toBeTrue()
            ->and($event->retirementDate->eq($datetime))->toBeTrue();

        return true;
    });
});

test('it retires a released manager at the current datetime by default', function () {
    $manager = Manager::factory()->released()->create();
    $datetime = now();

    $this->managerRepository
        ->shouldNotReceive('reinstate');

    $this->managerRepository
        ->shouldNotReceive('clearInjury');

    $this->managerRepository
        ->shouldNotReceive('release');

    $this->managerRepository
        ->shouldReceive('retire')
        ->once()
        ->withArgs(function (Manager $retirableManager, Carbon $retirementDate) use ($manager, $datetime) {
            expect($retirableManager->is($manager))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($manager);

    resolve(RetireAction::class)->handle($manager);

    Event::assertDispatched(ManagerRetired::class, function ($event) use ($manager, $datetime) {
        expect($event->manager->is($manager))->toBeTrue()
            ->and($event->retirementDate->eq($datetime))->toBeTrue();

        return true;
    });
});

test('it retires a released manager at a specific datetime', function () {
    $manager = Manager::factory()->released()->create();
    $datetime = now()->addDays(2);

    $this->managerRepository
        ->shouldNotReceive('reinstate');

    $this->managerRepository
        ->shouldNotReceive('clearInjury');

    $this->managerRepository
        ->shouldNotReceive('release');

    $this->managerRepository
        ->shouldReceive('retire')
        ->once()
        ->with($manager, $datetime)
        ->andReturns($manager);

    resolve(RetireAction::class)->handle($manager, $datetime);

    Event::assertDispatched(ManagerRetired::class, function ($event) use ($manager, $datetime) {
        expect($event->manager->is($manager))->toBeTrue()
            ->and($event->retirementDate->eq($datetime))->toBeTrue();

        return true;
    });
});

test('it throws exception for retiring a non retirable manager', function ($factoryState) {
    $manager = Manager::factory()->{$factoryState}()->create();

    resolve(RetireAction::class)->handle($manager);
})->throws(CannotBeRetiredException::class)->with([
    'retired',
    'withFutureEmployment',
    'unemployed',
]);
