<?php

declare(strict_types=1);

use App\Actions\Referees\RetireAction;
use App\Exceptions\Status\CannotBeRetiredException;
use App\Models\Referees\Referee;
use App\Repositories\RefereeRepository;
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
        ->shouldReceive('endEmployment')
        ->once()
        ->with(Mockery::any(), Mockery::any());

    $this->refereeRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->with(Mockery::any(), Mockery::any());

    resolve(RetireAction::class)->handle($referee);
});

test('it retires a bookable referee at a specific datetime', function () {
    $referee = Referee::factory()->bookable()->create();
    $datetime = now()->addDays(2);

    $this->refereeRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->with($referee, $datetime);

    $this->refereeRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->with($referee, $datetime);

    resolve(RetireAction::class)->handle($referee, $datetime);
});

test('it retires a suspended referee at the current datetime by default', function () {
    $referee = Referee::factory()->suspended()->create();
    $datetime = now();

    $this->refereeRepository
        ->shouldReceive('endSuspension')
        ->once()
        ->with(Mockery::any(), Mockery::any());

    $this->refereeRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->with(Mockery::any(), Mockery::any());

    $this->refereeRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->with(Mockery::any(), Mockery::any());

    resolve(RetireAction::class)->handle($referee);
});

test('it retires a suspended referee at a specific datetime', function () {
    $referee = Referee::factory()->suspended()->create();
    $datetime = now()->addDays(2);

    $this->refereeRepository
        ->shouldReceive('endSuspension')
        ->once()
        ->with($referee, $datetime);

    $this->refereeRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->with($referee, $datetime);

    $this->refereeRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->with($referee, $datetime);

    resolve(RetireAction::class)->handle($referee, $datetime);
});

test('it retires an injured referee at the current datetime by default', function () {
    $referee = Referee::factory()->injured()->create();
    $datetime = now();

    $this->refereeRepository
        ->shouldReceive('endInjury')
        ->once()
        ->with(Mockery::any(), Mockery::any());

    $this->refereeRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->with(Mockery::any(), Mockery::any());

    $this->refereeRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->with(Mockery::any(), Mockery::any());

    resolve(RetireAction::class)->handle($referee);
});

test('it retires an injured referee at a specific datetime', function () {
    $referee = Referee::factory()->injured()->create();
    $datetime = now()->addDays(2);

    $this->refereeRepository
        ->shouldReceive('endInjury')
        ->once()
        ->with($referee, $datetime);

    $this->refereeRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->with($referee, $datetime);

    $this->refereeRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->with($referee, $datetime);

    resolve(RetireAction::class)->handle($referee, $datetime);
});

test('it retires a released referee at the current datetime by default', function () {
    $referee = Referee::factory()->released()->create();
    $datetime = now();

    $this->refereeRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->with(Mockery::any(), Mockery::any());

    resolve(RetireAction::class)->handle($referee);
});

test('it retires a released referee at a specific datetime', function () {
    $referee = Referee::factory()->released()->create();
    $datetime = now()->addDays(2);

    $this->refereeRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->with($referee, $datetime);

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

test('referee retirement status checks work correctly', function () {
    $employedReferee = Referee::factory()->employed()->make();
    $retiredReferee = Referee::factory()->unemployed()->make(); // Use unemployed as a stand-in for not retirable

    expect($employedReferee->canBeRetired())->toBeTrue();
    expect($retiredReferee->canBeRetired())->toBeFalse();
});
