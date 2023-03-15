<?php

use App\Actions\Referees\ClearInjuryAction;
use App\Exceptions\CannotBeClearedFromInjuryException;
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

test('it clears an injury of an injured referee at the current datetime by default', function () {
    $referee = Referee::factory()->injured()->create();
    $datetime = now();

    $this->refereeRepository
        ->shouldReceive('clearInjury')
        ->once()
        ->withArgs(function (Referee $unretireReferee, Carbon $recoveryDate) use ($referee, $datetime) {
            expect($unretireReferee->is($referee))->toBeTrue();
            expect($recoveryDate->equalTo($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($referee);

    ClearInjuryAction::run($referee);
});

test('it clears an injury of an injured referee at a specific datetime', function () {
    $referee = Referee::factory()->injured()->create();
    $datetime = now()->addDays(2);

    $this->refereeRepository
        ->shouldReceive('clearInjury')
        ->once()
        ->with($referee, $datetime)
        ->andReturn($referee);

    ClearInjuryAction::run($referee, $datetime);
});

test('invoke throws exception for injuring a non injurable referee', function ($factoryState) {
    $this->withoutExceptionHandling();

    $referee = Referee::factory()->{$factoryState}()->create();

    ClearInjuryAction::run($referee);
})->throws(CannotBeClearedFromInjuryException::class)->with([
    'unemployed',
    'released',
    'withFutureEmployment',
    'bookable',
    'retired',
    'suspended',
]);
