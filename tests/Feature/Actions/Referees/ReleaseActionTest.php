<?php

use App\Actions\Referees\ReleaseAction;
use App\Exceptions\CannotBeReleasedException;
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

test('it releases a bookable referee at the current datetime by default', function () {
    $referee = Referee::factory()->bookable()->create();
    $datetime = now();

    $this->refereeRepository
        ->shouldReceive('reinstate')
        ->shouldNotReceive('clearInjury');

    $this->refereeRepository
        ->shouldReceive('release')
        ->once()
        ->withArgs(function (Referee $releasableReferee, Carbon $releaseDate) use ($referee, $datetime) {
            expect($releasableReferee->is($referee))->toBeTrue();
            expect($releaseDate->equalTo($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($referee);

    ReleaseAction::run($referee);
});

test('it releases an bookable referee at a specific datetime', function () {
    $referee = Referee::factory()->bookable()->create();
    $datetime = now()->addDays(2);

    $this->refereeRepository
        ->shouldReceive('reinstate')
        ->shouldNotReceive('clearInjury');

    $this->refereeRepository
        ->shouldReceive('release')
        ->once()
        ->with($referee, $datetime)
        ->andReturn($referee);

    ReleaseAction::run($referee, $datetime);
});

test('it releases a suspended referee at the current datetime by default', function () {
    $referee = Referee::factory()->suspended()->create();
    $datetime = now();

    $this->refereeRepository
        ->shouldReceive('reinstate')
        ->once()
        ->withArgs(function (Referee $reinstatableReferee, Carbon $releaseDate) use ($referee, $datetime) {
            expect($reinstatableReferee->is($referee))->toBeTrue();
            expect($releaseDate->equalTo($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($referee);

    $this->refereeRepository
        ->shouldNotReceive('clearInjury');

    $this->refereeRepository
        ->shouldReceive('release')
        ->once()
        ->withArgs(function (Referee $releasableReferee, Carbon $releaseDate) use ($referee, $datetime) {
            expect($releasableReferee->is($referee))->toBeTrue();
            expect($releaseDate->equalTo($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($referee);

    ReleaseAction::run($referee);
});

test('it releases a suspended referee at a specific datetime', function () {
    $referee = Referee::factory()->suspended()->create();
    $datetime = now()->addDays(2);

    $this->refereeRepository
        ->shouldReceive('reinstate')
        ->once()
        ->with($referee, $datetime)
        ->andReturn($referee);

    $this->refereeRepository
        ->shouldNotReceive('clearInjury');

    $this->refereeRepository
        ->shouldReceive('release')
        ->once()
        ->with($referee, $datetime)
        ->andReturn($referee);

    ReleaseAction::run($referee, $datetime);
});

test('invoke throws an exception for releasing a non releasable referee', function ($factoryState) {
    $referee = Referee::factory()->{$factoryState}()->create();

    ReleaseAction::run($referee);
})->throws(CannotBeReleasedException::class)->with([
    'unemployed',
    'withFutureEmployment',
    'released',
    'retired',
]);
