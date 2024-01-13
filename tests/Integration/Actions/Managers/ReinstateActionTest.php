<?php

declare(strict_types=1);

use App\Actions\Managers\ReinstateAction;
use App\Exceptions\CannotBeReinstatedException;
use App\Models\Manager;
use App\Repositories\ManagerRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    Event::fake();

    testTime()->freeze();

    $this->managerRepository = Mockery::mock(ManagerRepository::class);
});

test('it reinstates a suspended manager at the current datetime by default', function () {
    $manager = Manager::factory()->suspended()->create();
    $datetime = now();

    $this->managerRepository
        ->shouldReceive('reinstate')
        ->once()
        ->withArgs(function (Manager $reinstatableManager, Carbon $reinstatementDate) use ($manager, $datetime) {
            expect($reinstatableManager->is($manager))->toBeTrue()
                ->and($reinstatementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($manager);

    ReinstateAction::run($manager);
});

test('it reinstates a suspended manager at a specific datetime', function () {
    $manager = Manager::factory()->suspended()->create();
    $datetime = now()->addDays(2);

    $this->managerRepository
        ->shouldReceive('reinstate')
        ->once()
        ->with($manager, $datetime)
        ->andReturn($manager);

    ReinstateAction::run($manager, $datetime);
});

test('it throws exception for reinstating a non reinstatable manager', function ($factoryState) {
    $manager = Manager::factory()->{$factoryState}()->create();

    ReinstateAction::run($manager);
})->throws(CannotBeReinstatedException::class)->with([
    'available',
    'unemployed',
    'injured',
    'released',
    'withFutureEmployment',
    'retired',
]);
