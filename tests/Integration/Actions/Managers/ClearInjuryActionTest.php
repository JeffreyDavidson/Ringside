<?php

declare(strict_types=1);

use App\Actions\Managers\HealAction;
use App\Exceptions\Status\CannotBeClearedFromInjuryException;
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

test('it clears an injury of an injured manager at the current datetime by default', function () {
    $manager = Manager::factory()->injured()->create();
    $datetime = now();

    $this->managerRepository
        ->shouldReceive('endInjury')
        ->once()
        ->withArgs(function (Manager $unretireManager, Carbon $recoveryDate) use ($manager, $datetime) {
            expect($unretireManager->is($manager))->toBeTrue()
                ->and($recoveryDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($manager);

    resolve(HealAction::class)->handle($manager);
});

test('it clears an injury of an injured manager at a specific datetime', function () {
    $manager = Manager::factory()->injured()->create();
    $datetime = now()->addDays(2);

    $this->managerRepository
        ->shouldReceive('endInjury')
        ->once()
        ->with($manager, $datetime)
        ->andReturn($manager);

    resolve(HealAction::class)->handle($manager, $datetime);
});

test('invoke throws exception for injuring a non injurable manager', function ($factoryState) {
    $manager = Manager::factory()->{$factoryState}()->create();

    resolve(HealAction::class)->handle($manager);
})->throws(CannotBeClearedFromInjuryException::class)->with([
    'unemployed',
    'released',
    'withFutureEmployment',
    'available',
    'retired',
    'suspended',
]);
