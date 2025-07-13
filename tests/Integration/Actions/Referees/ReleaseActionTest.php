<?php

declare(strict_types=1);

use App\Actions\Referees\ReleaseAction;
use App\Exceptions\Status\CannotBeReleasedException;
use App\Models\Referees\Referee;
use App\Repositories\RefereeRepository;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->refereeRepository = $this->mock(RefereeRepository::class);
});

test('referee release status checks work correctly', function () {
    $employedReferee = Referee::factory()->employed()->make();
    $unemployedReferee = Referee::factory()->unemployed()->make();

    expect($employedReferee->canBeReleased())->toBeTrue();
    expect($unemployedReferee->canBeReleased())->toBeFalse();
});

test('it releases a bookable referee at the current datetime by default', function () {
    $referee = Referee::factory()->bookable()->create();
    $datetime = now();

    $this->refereeRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->with(Mockery::any(), Mockery::any());

    resolve(ReleaseAction::class)->handle($referee);
});

test('it releases an bookable referee at a specific datetime', function () {
    $referee = Referee::factory()->bookable()->create();
    $datetime = now()->addDays(2);

    $this->refereeRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->with($referee, $datetime);

    resolve(ReleaseAction::class)->handle($referee, $datetime);
});

test('it releases a suspended referee at the current datetime by default', function () {
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

    resolve(ReleaseAction::class)->handle($referee);
});

test('it releases a suspended referee at a specific datetime', function () {
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

    resolve(ReleaseAction::class)->handle($referee, $datetime);
});

test('it throws an exception for releasing a non releasable referee', function ($factoryState) {
    $referee = Referee::factory()->{$factoryState}()->create();

    resolve(ReleaseAction::class)->handle($referee);
})->throws(CannotBeReleasedException::class)->with([
    'unemployed',
    'withFutureEmployment',
    'released',
    'retired',
]);
