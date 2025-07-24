<?php

declare(strict_types=1);

use App\Actions\Referees\RetireAction;
use App\Exceptions\Status\CannotBeRetiredException;
use App\Models\Referees\Referee;
use App\Repositories\RefereeRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    Event::fake();

    testTime()->freeze();

    $this->refereeRepository = $this->mock(RefereeRepository::class);
});

test('it retires a bookable referee at the current datetime by default', function () {
    $referee = Referee::factory()->bookable()->create();
    $datetime = now();

    $this->refereeRepository
        ->shouldNotReceive('endSuspension');

    $this->refereeRepository
        ->shouldNotReceive('endInjury');

    $this->refereeRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->withArgs(function (Referee $releasableReferee, Carbon $retirementDate) use ($referee, $datetime) {
            expect($releasableReferee->is($referee))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($referee);

    $this->refereeRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->withArgs(function (Referee $retirableReferee, Carbon $retirementDate) use ($referee, $datetime) {
            expect($retirableReferee->is($referee))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($referee);

    resolve(RetireAction::class)->handle($referee);
});

test('it retires a bookable referee at a specific datetime', function () {
    $referee = Referee::factory()->bookable()->create();
    $datetime = now()->addDays(2);

    $this->refereeRepository
        ->shouldNotReceive('endSuspension');

    $this->refereeRepository
        ->shouldNotReceive('endInjury');

    $this->refereeRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->with($referee, $datetime)
        ->andReturns($referee);

    $this->refereeRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->with($referee, $datetime)
        ->andReturns($referee);

    resolve(RetireAction::class)->handle($referee, $datetime);
});

test('it retires a suspended referee at the current datetime by default', function () {
    $referee = Referee::factory()->suspended()->create();
    $datetime = now();

    $this->refereeRepository
        ->shouldReceive('endSuspension')
        ->once()
        ->withArgs(function (Referee $reinstatableReferee, Carbon $retirementDate) use ($referee, $datetime) {
            expect($reinstatableReferee->is($referee))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($referee);

    $this->refereeRepository
        ->shouldNotReceive('endInjury');

    $this->refereeRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->withArgs(function (Referee $releasableReferee, Carbon $retirementDate) use ($referee, $datetime) {
            expect($releasableReferee->is($referee))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($referee);

    $this->refereeRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->withArgs(function (Referee $retirableReferee, Carbon $retirementDate) use ($referee, $datetime) {
            expect($retirableReferee->is($referee))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($referee);

    resolve(RetireAction::class)->handle($referee);
});

test('it retires a suspended referee at a specific datetime', function () {
    $referee = Referee::factory()->suspended()->create();
    $datetime = now()->addDays(2);

    $this->refereeRepository
        ->shouldReceive('endSuspension')
        ->once()
        ->with($referee, $datetime)
        ->andReturns($referee);

    $this->refereeRepository
        ->shouldNotReceive('endInjury');

    $this->refereeRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->with($referee, $datetime)
        ->andReturns($referee);

    $this->refereeRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->with($referee, $datetime)
        ->andReturns($referee);

    resolve(RetireAction::class)->handle($referee, $datetime);
});

test('it retires an injured referee at the current datetime by default', function () {
    $referee = Referee::factory()->injured()->create();
    $datetime = now();

    $this->refereeRepository
        ->shouldNotReceive('endSuspension');

    $this->refereeRepository
        ->shouldReceive('endInjury')
        ->once()
        ->withArgs(function (Referee $clearableReferee, Carbon $retirementDate) use ($referee, $datetime) {
            expect($clearableReferee->is($referee))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($referee);

    $this->refereeRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->withArgs(function (Referee $releasableReferee, Carbon $retirementDate) use ($referee, $datetime) {
            expect($releasableReferee->is($referee))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($referee);

    $this->refereeRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->withArgs(function (Referee $retirableReferee, Carbon $retirementDate) use ($referee, $datetime) {
            expect($retirableReferee->is($referee))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($referee);

    resolve(RetireAction::class)->handle($referee);
});

test('it retires an injured referee at a specific datetime', function () {
    $referee = Referee::factory()->injured()->create();
    $datetime = now()->addDays(2);

    $this->refereeRepository
        ->shouldNotReceive('endSuspension');

    $this->refereeRepository
        ->shouldReceive('endInjury')
        ->once()
        ->with($referee, $datetime)
        ->andReturns($referee);

    $this->refereeRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->with($referee, $datetime)
        ->andReturns($referee);

    $this->refereeRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->with($referee, $datetime)
        ->andReturns($referee);

    resolve(RetireAction::class)->handle($referee, $datetime);
});

test('it retires a released referee at the current datetime by default', function () {
    $referee = Referee::factory()->released()->create();
    $datetime = now();

    $this->refereeRepository
        ->shouldNotReceive('endSuspension');

    $this->refereeRepository
        ->shouldNotReceive('endInjury');

    $this->refereeRepository
        ->shouldNotReceive('endEmployment');

    $this->refereeRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->withArgs(function (Referee $retirableReferee, Carbon $retirementDate) use ($referee, $datetime) {
            expect($retirableReferee->is($referee))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($referee);

    resolve(RetireAction::class)->handle($referee);
});

test('it retires a released referee at a specific datetime', function () {
    $referee = Referee::factory()->released()->create();
    $datetime = now()->addDays(2);

    $this->refereeRepository
        ->shouldNotReceive('endSuspension');

    $this->refereeRepository
        ->shouldNotReceive('endInjury');

    $this->refereeRepository
        ->shouldNotReceive('endEmployment');

    $this->refereeRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->with($referee, $datetime)
        ->andReturns($referee);

    resolve(RetireAction::class)->handle($referee, $datetime);
});

test('it throws exception for retiring a non retirable referee', function ($factoryState) {
    $referee = Referee::factory()->{$factoryState}()->create();

    resolve(RetireAction::class)->handle($referee);
})->throws(CannotBeRetiredException::class)->with([
    'retired',
    'withFutureEmployment',
    'unemployed',
]);
