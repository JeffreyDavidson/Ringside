<?php

declare(strict_types=1);

use App\Actions\Managers\EmployAction;
use App\Exceptions\Status\CannotBeEmployedException;
use App\Models\Managers\Manager;
use App\Models\Managers\ManagerEmployment;
use App\Repositories\ManagerRepository;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->managerRepository = $this->mock(ManagerRepository::class);
});

test('it employs an employable manager at the current datetime by default', function ($factoryState) {
    $manager = Manager::factory()->{$factoryState}()->create();
    $datetime = now();

    $this->managerRepository
        ->shouldReceive('createEmployment')
        ->once()
        ->with(
            Mockery::on(fn ($m) => $m->id === $manager->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($manager);

    resolve(EmployAction::class)->handle($manager);
})->with([
    'unemployed',
    'released',
]);

test('it employs an employable manager at a specific datetime', function ($factoryState) {
    $manager = Manager::factory()->{$factoryState}()->create();
    $datetime = now()->addDays(2);

    $this->managerRepository
        ->shouldReceive('createEmployment')
        ->once()
        ->with(
            Mockery::on(fn ($m) => $m->id === $manager->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($manager);

    resolve(EmployAction::class)->handle($manager, $datetime);
})->with([
    'unemployed',
    'released',
]);

test('it employs a retired manager at the current datetime by default', function () {
    $manager = Manager::factory()->retired()->create();
    $datetime = now();

    $this->managerRepository
        ->shouldReceive('endRetirement')
        ->once()
        ->with(
            Mockery::on(fn ($m) => $m->id === $manager->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($manager);

    $this->managerRepository
        ->shouldReceive('createEmployment')
        ->once()
        ->with(
            Mockery::on(fn ($m) => $m->id === $manager->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($manager);

    resolve(EmployAction::class)->handle($manager);
});

test('it employs a retired manager at a specific datetime', function () {
    $manager = Manager::factory()->retired()->create();
    $datetime = now()->addDays(2);

    $this->managerRepository
        ->shouldReceive('endRetirement')
        ->once()
        ->with(
            Mockery::on(fn ($m) => $m->id === $manager->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($manager);

    $this->managerRepository
        ->shouldReceive('createEmployment')
        ->once()
        ->with(
            Mockery::on(fn ($m) => $m->id === $manager->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($manager);

    resolve(EmployAction::class)->handle($manager, $datetime);
});

test('it throws exception for employing a non employable manager', function ($factoryState) {
    $manager = Manager::factory()->{$factoryState}()->create();
    $manager = $manager->fresh();
    if ($factoryState === 'employed') {
        ManagerEmployment::factory()->for($manager)->create(['ended_at' => null]);
        $manager = Manager::with('employments')->find($manager->id);
    }

    $this->managerRepository
        ->shouldReceive('createEmployment')
        ->zeroOrMoreTimes();

    resolve(EmployAction::class)->handle($manager);
})->throws(CannotBeEmployedException::class)->with([
    'withFutureEmployment',
]);
