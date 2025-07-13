<?php

declare(strict_types=1);

use App\Actions\Referees\InjureAction;
use App\Exceptions\Status\CannotBeInjuredException;
use App\Models\Referees\Referee;
use App\Repositories\RefereeRepository;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->refereeRepository = $this->mock(RefereeRepository::class);
});

test('it injures a bookable referee at the current datetime by default', function () {
    $referee = Referee::factory()->bookable()->create();
    $datetime = now();

    $this->refereeRepository
        ->shouldReceive('createInjury')
        ->once()
        ->withArgs(function (Referee $injurableReferee, Carbon $injuryDate) use ($referee, $datetime) {
            expect($injurableReferee->is($referee))->toBeTrue()
                ->and($injuryDate->eq($datetime))->toBeTrue();

            return true;
        });

    resolve(InjureAction::class)->handle($referee);
});

test('it injures a bookable referee at a specific datetime', function () {
    $referee = Referee::factory()->bookable()->create();
    $datetime = now()->addDays(2);

    $this->refereeRepository
        ->shouldReceive('createInjury')
        ->once()
        ->with($referee, $datetime);

    resolve(InjureAction::class)->handle($referee, $datetime);
});

test('it throws exception for injuring a non injurable referee', function ($factoryState) {
    $referee = Referee::factory()->{$factoryState}()->create();

    resolve(InjureAction::class)->handle($referee);
})->throws(CannotBeInjuredException::class)->with([
    'unemployed',
    'suspended',
    'released',
    'withFutureEmployment',
    'retired',
    'injured',
]);

test('referee injury status checks work correctly', function () {
    $employedReferee = Referee::factory()->employed()->make();
    $injuredReferee = Referee::factory()->unemployed()->make(); // Use unemployed as a stand-in for not injurable

    expect($employedReferee->canBeInjured())->toBeTrue();
    expect($injuredReferee->canBeInjured())->toBeFalse();
});
