<?php

declare(strict_types=1);

use App\Actions\Referees\ClearInjuryAction;
use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Models\Referee;
use App\Repositories\RefereeRepository;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->refereeRepository = Mockery::mockRefereeRepository::class);
});

test('it clears an injury of an injured referee at the current datetime by default', function () {
    $referee = Referee::factory()->injured()->create();
    $datetime = now();

    $this->refereeRepository
        ->shouldReceive('clearInjury')
        ->once()
        ->withArgs(function (Referee $healedReferee, Carbon $recoveryDate) use ($referee, $datetime) {
            expect($healedReferee->is($referee))->toBeTrue()
                ->and($recoveryDate->eq($datetime))->toBeTrue();

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

test('it throws exception for injuring a non injurable referee', function ($factoryState) {
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
