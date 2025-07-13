<?php

declare(strict_types=1);

use App\Actions\Managers\RetireAction;
use App\Exceptions\Status\CannotBeRetiredException;
use App\Models\Managers\Manager;
use App\Repositories\ManagerRepository;
use Illuminate\Support\Facades\Event;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    Event::fake();

    testTime()->freeze();

    $this->managerRepository = $this->mock(ManagerRepository::class);
});

test('it retires a available manager at the current datetime by default', function () {
    $manager = Manager::factory()->available()->create();
    $datetime = now();

    $this->managerRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->with(
            Mockery::on(fn ($m) => $m->id === $manager->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($manager);

    $this->managerRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->with(
            Mockery::on(fn ($m) => $m->id === $manager->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($manager);

    resolve(RetireAction::class)->handle($manager);
});

test('it retires a available manager at a specific datetime', function () {
    $manager = Manager::factory()->available()->create();
    $datetime = now()->addDays(2);

    $this->managerRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->with(
            Mockery::on(fn ($m) => $m->id === $manager->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($manager);

    $this->managerRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->with(
            Mockery::on(fn ($m) => $m->id === $manager->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($manager);

    resolve(RetireAction::class)->handle($manager, $datetime);
});

test('it retires a suspended manager at the current datetime by default', function () {
    $manager = Manager::factory()->suspended()->create();
    $datetime = now();

    $this->managerRepository
        ->shouldReceive('endSuspension')
        ->once()
        ->with(
            Mockery::on(fn ($m) => $m->id === $manager->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($manager);

    $this->managerRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->with(
            Mockery::on(fn ($m) => $m->id === $manager->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($manager);

    $this->managerRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->with(
            Mockery::on(fn ($m) => $m->id === $manager->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($manager);

    resolve(RetireAction::class)->handle($manager);
});

test('it retires a suspended manager at a specific datetime', function () {
    $manager = Manager::factory()->suspended()->create();
    $datetime = now()->addDays(2);

    $this->managerRepository
        ->shouldReceive('endSuspension')
        ->once()
        ->with(
            Mockery::on(fn ($m) => $m->id === $manager->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($manager);

    $this->managerRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->with(
            Mockery::on(fn ($m) => $m->id === $manager->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($manager);

    $this->managerRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->with(
            Mockery::on(fn ($m) => $m->id === $manager->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($manager);

    resolve(RetireAction::class)->handle($manager, $datetime);
});

test('it retires an injured manager at the current datetime by default', function () {
    $manager = Manager::factory()->injured()->create();
    $datetime = now();

    $this->managerRepository
        ->shouldReceive('endInjury')
        ->once()
        ->with(
            Mockery::on(fn ($m) => $m->id === $manager->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($manager);

    $this->managerRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->with(
            Mockery::on(fn ($m) => $m->id === $manager->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($manager);

    $this->managerRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->with(
            Mockery::on(fn ($m) => $m->id === $manager->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($manager);

    resolve(RetireAction::class)->handle($manager);
});

test('it retires an injured manager at a specific datetime', function () {
    $manager = Manager::factory()->injured()->create();
    $datetime = now()->addDays(2);

    $this->managerRepository
        ->shouldReceive('endInjury')
        ->once()
        ->with(
            Mockery::on(fn ($m) => $m->id === $manager->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($manager);

    $this->managerRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->with(
            Mockery::on(fn ($m) => $m->id === $manager->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($manager);

    $this->managerRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->with(
            Mockery::on(fn ($m) => $m->id === $manager->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($manager);

    resolve(RetireAction::class)->handle($manager, $datetime);
});

test('it retires a released manager at the current datetime by default', function () {
    $manager = Manager::factory()->released()->create();
    $datetime = now();

    $this->managerRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->with(
            Mockery::on(fn ($m) => $m->id === $manager->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($manager);

    resolve(RetireAction::class)->handle($manager);
});

test('it retires a released manager at a specific datetime', function () {
    $manager = Manager::factory()->released()->create();
    $datetime = now()->addDays(2);

    $this->managerRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->with(
            Mockery::on(fn ($m) => $m->id === $manager->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($manager);

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

test('manager retirement status checks work correctly', function () {
    $employedManager = Manager::factory()->employed()->make();
    $retiredManager = Manager::factory()->unemployed()->make(); // Use unemployed as a stand-in for not retirable

    expect($employedManager->canBeRetired())->toBeTrue();
    expect($retiredManager->canBeRetired())->toBeFalse();
});
