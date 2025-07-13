<?php

declare(strict_types=1);

use App\Actions\Referees\SuspendAction;
use App\Exceptions\Status\CannotBeSuspendedException;
use App\Models\Referees\Referee;
use App\Repositories\RefereeRepository;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->refereeRepository = $this->mock(RefereeRepository::class);
});

test('it suspends a bookable referee at the current datetime by default', function () {
    $referee = Referee::factory()->bookable()->create();
    $datetime = now();

    $this->refereeRepository
        ->shouldReceive('createSuspension')
        ->once()
        ->withArgs(function (Referee $suspendableReferee, Carbon $suspensionDate) use ($referee, $datetime) {
            expect($suspendableReferee->is($referee))->toBeTrue()
                ->and($suspensionDate->eq($datetime))->toBeTrue();

            return true;
        });

    resolve(SuspendAction::class)->handle($referee);
});

test('it suspends a bookable referee at a specific datetime', function () {
    $referee = Referee::factory()->bookable()->create();
    $datetime = now()->addDays(2);

    $this->refereeRepository
        ->shouldReceive('createSuspension')
        ->once()
        ->with($referee, $datetime);

    resolve(SuspendAction::class)->handle($referee, $datetime);
});

test('invoke throws exception for suspending a non suspendable referee', function ($factoryState) {
    $referee = Referee::factory()->{$factoryState}()->create();

    resolve(SuspendAction::class)->handle($referee);
})->throws(CannotBeSuspendedException::class)->with([
    'unemployed',
    'withFutureEmployment',
    'injured',
    'released',
    'retired',
    'suspended',
]);

test('referee suspension status checks work correctly', function () {
    $employedReferee = Referee::factory()->employed()->make();
    $suspendedReferee = Referee::factory()->unemployed()->make(); // Use unemployed as a stand-in for not suspendable

    expect($employedReferee->canBeSuspended())->toBeTrue();
    expect($suspendedReferee->canBeSuspended())->toBeFalse();
});
