<?php

declare(strict_types=1);

use App\Actions\Referees\InjureAction;
use App\Exceptions\CannotBeInjuredException;
use App\Models\Referee;
use App\Repositories\RefereeRepository;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->refereeRepository = Mockery::mockRefereeRepository::class);
});

test('it injures a bookable referee at the current datetime by default', function () {
    $referee = Referee::factory()->bookable()->create();
    $datetime = now();

    $this->refereeRepository
        ->shouldReceive('injure')
        ->once()
        ->withArgs(function (Referee $injurableReferee, Carbon $injuryDate) use ($referee, $datetime) {
            expect($injurableReferee->is($referee))->toBeTrue()
                ->and($injuryDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($referee);

    InjureAction::run($referee);
});

test('it injures a bookable referee at a specific datetime', function () {
    $referee = Referee::factory()->bookable()->create();
    $datetime = now()->addDays(2);

    $this->refereeRepository
        ->shouldReceive('injure')
        ->once()
        ->with($referee, $datetime)
        ->andReturn($referee);

    InjureAction::run($referee, $datetime);
});

test('it throws exception for injuring a non injurable referee', function ($factoryState) {
    $referee = Referee::factory()->{$factoryState}()->create();

    InjureAction::run($referee);
})->throws(CannotBeInjuredException::class)->with([
    'unemployed',
    'suspended',
    'released',
    'withFutureEmployment',
    'retired',
    'injured',
]);
