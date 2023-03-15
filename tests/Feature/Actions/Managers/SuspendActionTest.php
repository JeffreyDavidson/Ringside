<?php

use App\Actions\Managers\SuspendAction;
use App\Exceptions\CannotBeSuspendedException;
use App\Models\Manager;
use App\Repositories\ManagerRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use function Pest\Laravel\mock;
use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    Event::fake();

    testTime()->freeze();

    $this->managerRepository = mock(ManagerRepository::class);
});

test('it suspends an available manager at the current datetime by default', function () {
    $manager = Manager::factory()->available()->create();
    $datetime = now();

    $this->managerRepository
        ->shouldReceive('suspend')
        ->once()
        ->withArgs(function (Manager $suspendableManager, Carbon $suspensionDate) use ($manager, $datetime) {
            expect($suspendableManager->is($manager))->toBeTrue();
            expect($suspensionDate->equalTo($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($manager);

    SuspendAction::run($manager);
});

test('it suspends an available manager at a specific datetime', function () {
    $manager = Manager::factory()->available()->create();
    $datetime = now()->addDays(2);

    $this->managerRepository
        ->shouldReceive('suspend')
        ->once()
        ->with($manager, $datetime)
        ->andReturn($manager);

    SuspendAction::run($manager, $datetime);
});

test('invoke throws exception for suspending a non suspendable manager', function ($factoryState) {
    $manager = Manager::factory()->{$factoryState}()->create();

    SuspendAction::run($manager);
})->throws(CannotBeSuspendedException::class)->with([
    'unemployed',
    'withFutureEmployment',
    'injured',
    'released',
    'retired',
    'suspended',
]);
