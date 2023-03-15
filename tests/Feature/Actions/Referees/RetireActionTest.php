<?php

use App\Actions\Referees\RetireAction;
use App\Exceptions\CannotBeRetiredException;
use App\Models\Referee;
use App\Repositories\RefereeRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use function Pest\Laravel\mock;
use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    Event::fake();

    testTime()->freeze();

    $this->refereeRepository = mock(RefereeRepository::class);
});

test('it retires a bookable referee at the current datetime by default', function () {
    $referee = Referee::factory()->bookable()->create();
    $datetime = now();

    $this->refereeRepository
        ->shouldNotReceive('reinstate')
        ->shouldNotReceive('clearInjury');

    $this->refereeRepository
        ->shouldReceive('release')
        ->once()
        ->withArgs(function (Referee $releasableReferee, Carbon $retirementDate) use ($referee, $datetime) {
            expect($releasableReferee->is($referee))->toBeTrue();
            expect($retirementDate->equalTo($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($referee);

    $this->refereeRepository
        ->shouldReceive('retire')
        ->once()
        ->withArgs(function (Referee $retirableReferee, Carbon $retirementDate) use ($referee, $datetime) {
            expect($retirableReferee->is($referee))->toBeTrue();
            expect($retirementDate->equalTo($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($referee);

    RetireAction::run($referee);
});

test('it retires a bookable referee at a specific datetime', function () {
    $referee = Referee::factory()->bookable()->create();
    $datetime = now()->addDays(2);

    $this->refereeRepository
        ->shouldNotReceive('reinstate')
        ->shouldNotReceive('clearInjury');

    $this->refereeRepository
        ->shouldReceive('release')
        ->once()
        ->with($referee, $datetime)
        ->andReturns($referee);

    $this->refereeRepository
        ->shouldReceive('retire')
        ->once()
        ->with($referee, $datetime)
        ->andReturns($referee);

    RetireAction::run($referee, $datetime);
});

test('it retires a suspended referee at the current datetime by default', function () {
    $referee = Referee::factory()->suspended()->create();
    $datetime = now();

    $this->refereeRepository
        ->shouldReceive('reinstate')
        ->once()
        ->withArgs(function (Referee $reinstatableReferee, Carbon $retirementDate) use ($referee, $datetime) {
            expect($reinstatableReferee->is($referee))->toBeTrue();
            expect($retirementDate->equalTo($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($referee);

    $this->refereeRepository
        ->shouldNotReceive('clearInjury');

    $this->refereeRepository
        ->shouldReceive('release')
        ->once()
        ->withArgs(function (Referee $releasableReferee, Carbon $retirementDate) use ($referee, $datetime) {
            expect($releasableReferee->is($referee))->toBeTrue();
            expect($retirementDate->equalTo($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($referee);

    $this->refereeRepository
        ->shouldReceive('retire')
        ->once()
        ->withArgs(function (Referee $retirableReferee, Carbon $retirementDate) use ($referee, $datetime) {
            expect($retirableReferee->is($referee))->toBeTrue();
            expect($retirementDate->equalTo($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($referee);

    RetireAction::run($referee);
});

test('it retires a suspended referee at a specific datetime', function () {
    $referee = Referee::factory()->suspended()->create();
    $datetime = now()->addDays(2);

    $this->refereeRepository
        ->shouldReceive('reinstate')
        ->once()
        ->with($referee, $datetime)
        ->andReturns($referee);

    $this->refereeRepository
        ->shouldNotReceive('clearInjury');

    $this->refereeRepository
        ->shouldReceive('release')
        ->once()
        ->with($referee, $datetime)
        ->andReturns($referee);

    $this->refereeRepository
        ->shouldReceive('retire')
        ->once()
        ->with($referee, $datetime)
        ->andReturns($referee);

    RetireAction::run($referee, $datetime);
});

test('it retires an injured referee at the current datetime by default', function () {
    $referee = Referee::factory()->injured()->create();
    $datetime = now();

    $this->refereeRepository
        ->shouldNotReceive('reinstate');

    $this->refereeRepository
        ->shouldReceive('clearInjury')
        ->once()
        ->withArgs(function (Referee $clearableReferee, Carbon $retirementDate) use ($referee, $datetime) {
            expect($clearableReferee->is($referee))->toBeTrue();
            expect($retirementDate->equalTo($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($referee);

    $this->refereeRepository
        ->shouldReceive('release')
        ->once()
        ->withArgs(function (Referee $releasableReferee, Carbon $retirementDate) use ($referee, $datetime) {
            expect($releasableReferee->is($referee))->toBeTrue();
            expect($retirementDate->equalTo($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($referee);

    $this->refereeRepository
        ->shouldReceive('retire')
        ->once()
        ->withArgs(function (Referee $retirableReferee, Carbon $retirementDate) use ($referee, $datetime) {
            expect($retirableReferee->is($referee))->toBeTrue();
            expect($retirementDate->equalTo($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($referee);

    RetireAction::run($referee);
});

test('it retires an injured referee at a specific datetime', function () {
    $referee = Referee::factory()->injured()->create();
    $datetime = now()->addDays(2);

    $this->refereeRepository
        ->shouldNotReceive('reinstate');

    $this->refereeRepository
        ->shouldReceive('clearInjury')
        ->once()
        ->with($referee, $datetime)
        ->andReturns($referee);

    $this->refereeRepository
        ->shouldReceive('release')
        ->once()
        ->with($referee, $datetime)
        ->andReturns($referee);

    $this->refereeRepository
        ->shouldReceive('retire')
        ->once()
        ->with($referee, $datetime)
        ->andReturns($referee);

    RetireAction::run($referee, $datetime);
});

test('it retires a released referee at the current datetime by default', function () {
    $referee = Referee::factory()->released()->create();
    $datetime = now();

    $this->refereeRepository
        ->shouldNotReceive('reinstate')
        ->shouldNotReceive('clearInjury')
        ->shouldNotReceive('release');

    $this->refereeRepository
        ->shouldReceive('retire')
        ->once()
        ->withArgs(function (Referee $retirableReferee, Carbon $retirementDate) use ($referee, $datetime) {
            expect($retirableReferee->is($referee))->toBeTrue();
            expect($retirementDate->equalTo($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($referee);

    RetireAction::run($referee);
});

test('it retires a released referee at a specific datetime', function () {
    $referee = Referee::factory()->released()->create();
    $datetime = now()->addDays(2);

    $this->refereeRepository
        ->shouldNotReceive('reinstate')
        ->shouldNotReceive('clearInjury')
        ->shouldNotReceive('release');

    $this->refereeRepository
        ->shouldReceive('retire')
        ->once()
        ->with($referee, $datetime)
        ->andReturns($referee);

    RetireAction::run($referee, $datetime);
});

test('invoke throws exception for retiring a non retirable referee', function ($factoryState) {
    $referee = Referee::factory()->{$factoryState}()->create();

    RetireAction::run($referee);
})->throws(CannotBeRetiredException::class)->with([
    'retired',
    'withFutureEmployment',
    'unemployed',
]);
