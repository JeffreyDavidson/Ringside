<?php

declare(strict_types=1);

use App\Actions\Managers\ClearInjuryAction;
use App\Exceptions\CannotBeClearedFromInjuryException;
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

test('it clears an injury of an injured manager at the current datetime by default', function () {
    $manager = Manager::factory()->injured()->create();
    $datetime = now();

    $this->managerRepository
        ->shouldReceive('clearInjury')
        ->once()
        ->withArgs(function (Manager $unretireManager, Carbon $recoveryDate) use ($manager, $datetime) {
            expect($unretireManager->is($manager))->toBeTrue()
                ->and($recoveryDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($manager);

    ClearInjuryAction::run($manager);
});

test('it clears an injury of an injured manager at a specific datetime', function () {
    $manager = Manager::factory()->injured()->create();
    $datetime = now()->addDays(2);

    $this->managerRepository
        ->shouldReceive('clearInjury')
        ->once()
        ->with($manager, $datetime)
        ->andReturn($manager);

    ClearInjuryAction::run($manager, $datetime);
});

test('invoke throws exception for injuring a non injurable manager', function ($factoryState) {
    $manager = Manager::factory()->{$factoryState}()->create();

    ClearInjuryAction::run($manager);
})->throws(CannotBeClearedFromInjuryException::class)->with([
    'unemployed',
    'released',
    'withFutureEmployment',
    'available',
    'retired',
    'suspended',
]);
