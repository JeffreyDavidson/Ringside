<?php

declare(strict_types=1);

use App\Actions\Referees\ReinstateAction;
use App\Exceptions\Status\CannotBeReinstatedException;
use App\Models\Referees\Referee;
use App\Repositories\RefereeRepository;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->refereeRepository = $this->mock(RefereeRepository::class);
});

test('it reinstates a suspended referee at the current datetime by default', function () {
    $referee = Referee::factory()->suspended()->create();
    $datetime = now();

    $this->refereeRepository
        ->shouldReceive('endSuspension')
        ->once()
        ->withArgs(function (Referee $reinstatableReferee, Carbon $reinstatementDate) use ($referee, $datetime) {
            expect($reinstatableReferee->is($referee))->toBeTrue()
                ->and($reinstatementDate->eq($datetime))->toBeTrue();

            return true;
        });

    resolve(ReinstateAction::class)->handle($referee);
});

test('it reinstates a suspended referee at a specific datetime', function () {
    $referee = Referee::factory()->suspended()->create();
    $datetime = now()->addDays(2);

    $this->refereeRepository
        ->shouldReceive('endSuspension')
        ->once()
        ->with($referee, $datetime);

    resolve(ReinstateAction::class)->handle($referee, $datetime);
});

test('invoke throws exception for reinstating a non reinstatable referee', function ($factoryState) {
    $referee = Referee::factory()->{$factoryState}()->create();

    $this->refereeRepository
        ->shouldReceive('endSuspension')
        ->never();

    try {
        resolve(ReinstateAction::class)->handle($referee);
        fwrite(STDERR, "No exception thrown for state: {$factoryState}\n");
        self::fail('No exception thrown');
    } catch (CannotBeReinstatedException $e) {
        $this->assertInstanceOf(CannotBeReinstatedException::class, $e);
    }
})->with([
    'bookable',
    'unemployed',
    'injured',
    'released',
    'withFutureEmployment',
    'retired',
]);
