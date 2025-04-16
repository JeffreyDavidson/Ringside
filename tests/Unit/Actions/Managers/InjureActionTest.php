<?php

declare(strict_types=1);

use App\Actions\Managers\InjureAction;
use App\Exceptions\CannotBeInjuredException;
use App\Models\Manager;
use App\Repositories\ManagerRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    Event::fake();

    testTime()->freeze();

    $this->managerRepository = $this->mock(ManagerRepository::class);
});

test('it injures an available manager at the current datetime by default', function () {
    $manager = Manager::factory()->available()->create();
    $datetime = now();

    $this->managerRepository
        ->shouldReceive('injure')
        ->once()
        ->withArgs(function (Manager $injurableManager, Carbon $injuryDate) use ($manager, $datetime) {
            expect($injurableManager->is($manager))->toBeTrue()
                ->and($injuryDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($manager);

    resolve(InjureAction::class)->handle($manager);
});

test('it injures an available manager at a specific datetime', function () {
    $manager = Manager::factory()->available()->create();
    $datetime = now()->addDays(2);

    $this->managerRepository
        ->shouldReceive('injure')
        ->once()
        ->with($manager, $datetime)
        ->andReturn($manager);

    resolve(InjureAction::class)->handle($manager, $datetime);
});

test('it throws exception for injuring a non injurable manager', function ($factoryState) {
    $manager = Manager::factory()->{$factoryState}()->create();

    resolve(InjureAction::class)->handle($manager);
})->throws(CannotBeInjuredException::class)->with([
    'unemployed',
    'suspended',
    'released',
    'withFutureEmployment',
    'retired',
    'injured',
]);
