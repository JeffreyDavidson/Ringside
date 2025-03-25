<?php

declare(strict_types=1);

use App\Actions\Managers\UnretireAction;
use App\Exceptions\CannotBeUnretiredException;
use App\Models\Manager;
use App\Repositories\ManagerRepository;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->managerRepository = $this->mock(ManagerRepository::class);
});

test('it unretires a retired manager at the current datetime by default', function () {
    $manager = Manager::factory()->retired()->create();
    $datetime = now();

    $this->managerRepository
        ->shouldReceive('unretire')
        ->once()
        ->withArgs(function (Manager $unretireManager, Carbon $unretireDate) use ($manager, $datetime) {
            expect($unretireManager->is($manager))->toBeTrue()
                ->and($unretireDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($manager);

    $this->managerRepository
        ->shouldReceive('employ')
        ->once()
        ->withArgs(function (Manager $employableManager, Carbon $unretireDate) use ($manager, $datetime) {
            expect($employableManager->is($manager))->toBeTrue()
                ->and($unretireDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($manager);

    UnretireAction::run($manager);
});

test('it unretires a retired manager at a specific datetime', function () {
    $manager = Manager::factory()->retired()->create();
    $datetime = now()->addDays(2);

    $this->managerRepository
        ->shouldReceive('unretire')
        ->once()
        ->with($manager, $datetime)
        ->andReturn($manager);

    $this->managerRepository
        ->shouldReceive('employ')
        ->once()
        ->with($manager, $datetime)
        ->andReturn($manager);

    UnretireAction::run($manager, $datetime);
});

test('it throws exception for unretiring a non unretirable manager', function ($factoryState) {
    $manager = Manager::factory()->{$factoryState}()->create();

    UnretireAction::run($manager);
})->throws(CannotBeUnretiredException::class)->with([
    'available',
    'withFutureEmployment',
    'injured',
    'released',
    'suspended',
    'unemployed',
]);
