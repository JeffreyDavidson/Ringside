<?php

declare(strict_types=1);

use App\Actions\Managers\ReleaseAction;
use App\Exceptions\Status\CannotBeReleasedException;
use App\Models\Managers\Manager;
use App\Repositories\ManagerRepository;
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
        ->shouldReceive('endEmployment')
        ->once()
        ->with(
            Mockery::on(fn ($m) => $m->id === $manager->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($manager);

    resolve(ReleaseAction::class)->handle($manager);
});

test('it releases an available manager at a specific datetime', function () {
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

    resolve(ReleaseAction::class)->handle($manager, $datetime);
});

test('it releases a suspended manager at the current datetime by default', function () {
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

    resolve(ReleaseAction::class)->handle($manager);
});

test('it releases a suspended manager at a specific datetime', function () {
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

    resolve(ReleaseAction::class)->handle($manager, $datetime);
});

test('it releases an injured manager at the current datetime by default', function () {
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

    resolve(ReleaseAction::class)->handle($manager);
});

test('it releases an injured manager at a specific datetime', function () {
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

    resolve(ReleaseAction::class)->handle($manager, $datetime);
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

test('manager release status checks work correctly', function () {
    $employedManager = Manager::factory()->employed()->make();
    $unemployedManager = Manager::factory()->unemployed()->make();

    expect($employedManager->canBeReleased())->toBeTrue();
    expect($unemployedManager->canBeReleased())->toBeFalse();
});
