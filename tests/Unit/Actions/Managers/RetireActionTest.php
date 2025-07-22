<?php

declare(strict_types=1);

use App\Actions\Managers\RetireAction;
use App\Exceptions\CannotBeRetiredException;
use App\Models\Managers\Manager;
use App\Repositories\ManagerRepository;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->managerRepository = $this->mock(ManagerRepository::class);
});

test('it retires an employed manager at the current datetime by default', function () {
    $manager = Manager::factory()->employed()->create();
    $datetime = now();

    $this->managerRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->withArgs(function (Manager $retirableManager, Carbon $retirementDate) use ($manager, $datetime) {
            expect($retirableManager->is($manager))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        });

    $this->managerRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->withArgs(function (Manager $retirableManager, Carbon $retirementDate) use ($manager, $datetime) {
            expect($retirableManager->is($manager))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        });

    resolve(RetireAction::class)->handle($manager);
});

test('it retires an employed manager at a specific datetime', function () {
    $manager = Manager::factory()->employed()->create();
    $datetime = now()->addDays(2);

    $this->managerRepository
        ->shouldNotReceive('endSuspension')
        ->shouldNotReceive('endInjury');

    $this->managerRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->with($manager, $datetime)
        ->andReturns($manager);

    $this->managerRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->with($manager, $datetime)
        ->andReturns($manager);

    resolve(RetireAction::class)->handle($manager, $datetime);
});

test('it retires a suspended manager at the current datetime by default', function () {
    $manager = Manager::factory()->suspended()->create();
    $datetime = now();

    $this->managerRepository
        ->shouldReceive('endSuspension')
        ->once()
        ->withArgs(function (Manager $reinstatableManager, Carbon $retirementDate) use ($manager, $datetime) {
            expect($reinstatableManager->is($manager))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($manager);

    $this->managerRepository
        ->shouldNotReceive('endInjury');

    $this->managerRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->withArgs(function (Manager $releasableManager, Carbon $retirementDate) use ($manager, $datetime) {
            expect($releasableManager->is($manager))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($manager);

    $this->managerRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->withArgs(function (Manager $retirableManager, Carbon $retirementDate) use ($manager, $datetime) {
            expect($retirableManager->is($manager))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($manager);

    resolve(RetireAction::class)->handle($manager);
});

test('it retires a suspended manager at a specific datetime', function () {
    $manager = Manager::factory()->suspended()->create();
    $datetime = now()->addDays(2);

    $this->managerRepository
        ->shouldReceive('endSuspension')
        ->once()
        ->with($manager, $datetime)
        ->andReturns($manager);

    $this->managerRepository
        ->shouldNotReceive('endInjury');

    $this->managerRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->with($manager, $datetime)
        ->andReturns($manager);

    $this->managerRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->with($manager, $datetime)
        ->andReturns($manager);

    resolve(RetireAction::class)->handle($manager, $datetime);
});

test('it retires an injured manager at the current datetime by default', function () {
    $manager = Manager::factory()->injured()->create();
    $datetime = now();

    $this->managerRepository
        ->shouldNotReceive('endSuspension');

    $this->managerRepository
        ->shouldReceive('endInjury')
        ->once()
        ->withArgs(function (Manager $clearableManager, Carbon $retirementDate) use ($manager, $datetime) {
            expect($clearableManager->is($manager))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($manager);

    $this->managerRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->withArgs(function (Manager $releasableManager, Carbon $retirementDate) use ($manager, $datetime) {
            expect($releasableManager->is($manager))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($manager);

    $this->managerRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->withArgs(function (Manager $retirableManager, Carbon $retirementDate) use ($manager, $datetime) {
            expect($retirableManager->is($manager))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($manager);

    resolve(RetireAction::class)->handle($manager);
});

test('it retires an injured manager at a specific datetime', function () {
    $manager = Manager::factory()->injured()->create();
    $datetime = now()->addDays(2);

    $this->managerRepository
        ->shouldNotReceive('endSuspension');

    $this->managerRepository
        ->shouldReceive('endInjury')
        ->once()
        ->with($manager, $datetime)
        ->andReturns($manager);

    $this->managerRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->with($manager, $datetime)
        ->andReturns($manager);

    $this->managerRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->with($manager, $datetime)
        ->andReturns($manager);

    resolve(RetireAction::class)->handle($manager, $datetime);
});

test('it retires a released manager at the current datetime by default', function () {
    $manager = Manager::factory()->released()->create();
    $datetime = now();

    $this->managerRepository
        ->shouldNotReceive('endSuspension');

    $this->managerRepository
        ->shouldNotReceive('endInjury');

    $this->managerRepository
        ->shouldNotReceive('endEmployment');

    $this->managerRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->withArgs(function (Manager $retirableManager, Carbon $retirementDate) use ($manager, $datetime) {
            expect($retirableManager->is($manager))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($manager);

    resolve(RetireAction::class)->handle($manager);
});

test('it retires a released manager at a specific datetime', function () {
    $manager = Manager::factory()->released()->create();
    $datetime = now()->addDays(2);

    $this->managerRepository
        ->shouldNotReceive('endSuspension');

    $this->managerRepository
        ->shouldNotReceive('endInjury');

    $this->managerRepository
        ->shouldNotReceive('endEmployment');

    $this->managerRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->with($manager, $datetime)
        ->andReturns($manager);

    resolve(RetireAction::class)->handle($manager, $datetime);
});

test('it throws exception for retiring a non retirable manager', function ($factoryState) {
    $manager = Manager::factory()->{$factoryState}()->create();

    resolve(RetireAction::class)->handle($manager);
})->throws(CannotBeRetiredException::class)->with([
    'retired',
    'withFutureEmployment',
    'unemployed',
]);
