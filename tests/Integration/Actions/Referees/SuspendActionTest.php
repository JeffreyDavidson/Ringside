<?php

declare(strict_types=1);

use App\Actions\Referees\SuspendAction;
use App\Exceptions\CannotBeSuspendedException;
use App\Models\Referee;
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
        ->shouldReceive('suspend')
        ->once()
        ->withArgs(function (Referee $suspendableReferee, Carbon $suspensionDate) use ($referee, $datetime) {
            expect($suspendableReferee->is($referee))->toBeTrue()
                ->and($suspensionDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($referee);

    SuspendAction::run($referee);
});

test('it suspends a bookable referee at a specific datetime', function () {
    $referee = Referee::factory()->bookable()->create();
    $datetime = now()->addDays(2);

    $this->refereeRepository
        ->shouldReceive('suspend')
        ->once()
        ->with($referee, $datetime)
        ->andReturn($referee);

    SuspendAction::run($referee, $datetime);
});

test('invoke throws exception for suspending a non suspendable referee', function ($factoryState) {
    $referee = Referee::factory()->{$factoryState}()->create();

    SuspendAction::run($referee);
})->throws(CannotBeSuspendedException::class)->with([
    'unemployed',
    'withFutureEmployment',
    'injured',
    'released',
    'retired',
    'suspended',
]);
