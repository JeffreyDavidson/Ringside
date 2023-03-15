<?php

use App\Actions\Managers\InjureAction;
use App\Exceptions\CannotBeInjuredException;
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

test('it injures an available manager at the current datetime by default', function () {
    $manager = Manager::factory()->available()->create();
    $datetime = now();

    $this->managerRepository
        ->shouldReceive('injure')
        ->once()
        ->withArgs(function (Manager $injurableManager, Carbon $injuryDate) use ($manager, $datetime) {
            expect($injurableManager->is($manager))->toBeTrue();
            expect($injuryDate->equalTo($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($manager);

    InjureAction::run($manager);
});

test('it injures an available manager at a specific datetime', function () {
    $manager = Manager::factory()->available()->create();
    $datetime = now()->addDays(2);

    $this->managerRepository
        ->shouldReceive('injure')
        ->once()
        ->with($manager, $datetime)
        ->andReturn($manager);

    InjureAction::run($manager, $datetime);
});

test('invoke throws exception for injuring a non injurable manager', function ($factoryState) {
    $manager = Manager::factory()->{$factoryState}()->create();

    InjureAction::run($manager);
})->throws(CannotBeInjuredException::class)->with([
    'unemployed',
    'suspended',
    'released',
    'withFutureEmployment',
    'retired',
    'injured',
]);
