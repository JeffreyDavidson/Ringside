<?php

declare(strict_types=1);

use App\Actions\Managers\UnretireAction;
use App\Exceptions\Status\CannotBeUnretiredException;
use App\Models\Managers\Manager;
use App\Repositories\ManagerRepository;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->managerRepository = $this->mock(ManagerRepository::class);
});

test('it unretires a retired manager at the current datetime by default', function () {
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

    resolve(UnretireAction::class)->handle($manager);
});

test('it unretires a retired manager at a specific datetime', function () {
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

    resolve(UnretireAction::class)->handle($manager, $datetime);
});

test('it throws exception for unretiring a non unretirable manager', function ($factoryState) {
    $manager = Manager::factory()->{$factoryState}()->create();

    resolve(UnretireAction::class)->handle($manager);
})->throws(CannotBeUnretiredException::class)->with([
    'available',
    'withFutureEmployment',
    'injured',
    'released',
    'suspended',
    'unemployed',
]);
