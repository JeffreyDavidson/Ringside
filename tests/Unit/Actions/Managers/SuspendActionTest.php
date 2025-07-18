<?php

declare(strict_types=1);

use App\Actions\Managers\SuspendAction;
use App\Exceptions\Status\CannotBeSuspendedException;
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

test('it suspends an employed manager at the current datetime by default', function () {
    $manager = Manager::factory()->employed()->create();
    $datetime = now();

    $this->managerRepository
        ->shouldReceive('createSuspension')
        ->once()
        ->withArgs(function (Manager $suspendableManager, Carbon $suspensionDate) use ($manager, $datetime) {
            expect($suspendableManager->is($manager))->toBeTrue()
                ->and($suspensionDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($manager);

    resolve(SuspendAction::class)->handle($manager);
});

test('it suspends an employed manager at a specific datetime', function () {
    $manager = Manager::factory()->employed()->create();
    $datetime = now()->addDays(2);

    $this->managerRepository
        ->shouldReceive('createSuspension')
        ->once()
        ->with($manager, $datetime)
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
