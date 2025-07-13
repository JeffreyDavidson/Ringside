<?php

declare(strict_types=1);

use App\Actions\Managers\SuspendAction;
use App\Exceptions\Status\CannotBeSuspendedException;
use App\Models\Managers\Manager;
use App\Repositories\ManagerRepository;
use Illuminate\Support\Facades\Event;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    Event::fake();

    testTime()->freeze();

    $this->managerRepository = $this->mock(ManagerRepository::class);
});

test('it suspends an available manager at the current datetime by default', function () {
    $manager = Manager::factory()->available()->create();
    $datetime = now();

    $this->managerRepository
        ->shouldReceive('createSuspension')
        ->once()
        ->with(
            Mockery::on(fn ($m) => $m->id === $manager->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($manager);

    resolve(SuspendAction::class)->handle($manager);
});

test('it suspends an available manager at a specific datetime', function () {
    $manager = Manager::factory()->available()->create();
    $datetime = now()->addDays(2);

    $this->managerRepository
        ->shouldReceive('createSuspension')
        ->once()
        ->with(
            Mockery::on(fn ($m) => $m->id === $manager->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($manager);

    resolve(SuspendAction::class)->handle($manager, $datetime);
});

test('it throws exception for suspending a non suspendable manager', function ($factoryState) {
    $manager = Manager::factory()->{$factoryState}()->create();

    resolve(SuspendAction::class)->handle($manager);
})->throws(CannotBeSuspendedException::class)->with([
    'unemployed',
    'withFutureEmployment',
    'injured',
    'released',
    'retired',
    'suspended',
]);

test('manager suspension status checks work correctly', function () {
    $employedManager = Manager::factory()->employed()->make();
    $suspendedManager = Manager::factory()->unemployed()->make(); // Use unemployed as a stand-in for not suspendable

    expect($employedManager->canBeSuspended())->toBeTrue();
    expect($suspendedManager->canBeSuspended())->toBeFalse();
});
