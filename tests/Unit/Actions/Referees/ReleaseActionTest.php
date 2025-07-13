<?php

declare(strict_types=1);

use App\Actions\Referees\ReleaseAction;
use App\Exceptions\CannotBeReleasedException;
use App\Models\Referees\Referee;
use App\Repositories\RefereeRepository;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->refereeRepository = $this->mock(RefereeRepository::class);
});

test('it releases a bookable referee at the current datetime by default', function () {
    $referee = Referee::factory()->bookable()->create();
    $datetime = now();

    $this->refereeRepository
        ->shouldReceive('reinstate');

    $this->refereeRepository
        ->shouldNotReceive('clearInjury');

    $this->refereeRepository
        ->shouldReceive('release')
        ->once()
        ->withArgs(function (Referee $releasableReferee, Carbon $releaseDate) use ($referee, $datetime) {
            expect($releasableReferee->is($referee))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($referee);

    resolve(ReleaseAction::class)->handle($referee);
});

test('it releases an bookable referee at a specific datetime', function () {
    $referee = Referee::factory()->bookable()->create();
    $datetime = now()->addDays(2);

    $this->refereeRepository
        ->shouldReceive('reinstate');

    $this->refereeRepository
        ->shouldNotReceive('clearInjury');

    $this->refereeRepository
        ->shouldReceive('release')
        ->once()
        ->with($referee, $datetime)
        ->andReturn($referee);

    resolve(ReleaseAction::class)->handle($referee, $datetime);
});

test('it releases a suspended referee at the current datetime by default', function () {
    $referee = Referee::factory()->suspended()->create();
    $datetime = now();

    $this->refereeRepository
        ->shouldReceive('reinstate')
        ->once()
        ->withArgs(function (Referee $reinstatableReferee, Carbon $releaseDate) use ($referee, $datetime) {
            expect($reinstatableReferee->is($referee))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($referee);

    $this->refereeRepository
        ->shouldNotReceive('clearInjury');

    $this->refereeRepository
        ->shouldReceive('release')
        ->once()
        ->withArgs(function (Referee $releasableReferee, Carbon $releaseDate) use ($referee, $datetime) {
            expect($releasableReferee->is($referee))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($referee);

    resolve(ReleaseAction::class)->handle($referee);
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
