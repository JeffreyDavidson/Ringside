<?php

use App\Actions\Referees\ReinstateAction;
use App\Exceptions\CannotBeReinstatedException;
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

test('it reinstates a suspended referee at the current datetime by default', function () {
    $referee = Referee::factory()->suspended()->create();
    $datetime = now();

    $this->refereeRepository
        ->shouldReceive('reinstate')
        ->once()
        ->withArgs(function (Referee $reinstatableReferee, Carbon $reinstatementDate) use ($referee, $datetime) {
            expect($reinstatableReferee->is($referee))->toBeTrue();
            expect($reinstatementDate->equalTo($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($referee);

    ReinstateAction::run($referee);
});

test('it reinstates a suspended referee at a specific datetime', function () {
    $referee = Referee::factory()->suspended()->create();
    $datetime = now()->addDays(2);

    $this->refereeRepository
        ->shouldReceive('reinstate')
        ->once()
        ->with($referee, $datetime)
        ->andReturn($referee);

    ReinstateAction::run($referee, $datetime);
});

test('invoke throws exception for reinstating a non reinstatable referee', function ($factoryState) {
    $referee = Referee::factory()->{$factoryState}()->create();

    ReinstateAction::run($referee);
})->throws(CannotBeReinstatedException::class)->with([
    'bookable',
    'unemployed',
    'injured',
    'released',
    'withFutureEmployment',
    'retired',
]);
