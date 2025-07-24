<?php

declare(strict_types=1);

use App\Actions\Referees\ClearInjuryAction;
use App\Exceptions\Status\CannotBeClearedFromInjuryException;
use App\Models\Referees\Referee;
use App\Repositories\RefereeRepository;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->refereeRepository = $this->mock(RefereeRepository::class);
});

test('it clears an injury of an injured referee at the current datetime by default', function () {
    $referee = Referee::factory()->injured()->create();
    $datetime = now();

    $this->refereeRepository
        ->shouldReceive('endInjury')
        ->once()
        ->withArgs(function (Referee $healedReferee, Carbon $recoveryDate) use ($referee, $datetime) {
            expect($healedReferee->is($referee))->toBeTrue()
                ->and($recoveryDate->eq($datetime))->toBeTrue();

            return true;
        });

    resolve(ClearInjuryAction::class)->handle($referee);
});

test('it clears an injury of an injured referee at a specific datetime', function () {
    $referee = Referee::factory()->injured()->create();
    $datetime = now()->addDays(2);

    $this->refereeRepository
        ->shouldReceive('endInjury')
        ->once()
        ->with($referee, $datetime);

    resolve(ClearInjuryAction::class)->handle($referee, $datetime);
});

test('it throws exception for injuring a non injurable referee', function ($factoryState) {
    $referee = Referee::factory()->{$factoryState}()->create();

    resolve(ClearInjuryAction::class)->handle($referee);
})->throws(CannotBeClearedFromInjuryException::class)->with([
    'unemployed',
    'released',
    'withFutureEmployment',
    'bookable',
    'retired',
    'suspended',
]);
