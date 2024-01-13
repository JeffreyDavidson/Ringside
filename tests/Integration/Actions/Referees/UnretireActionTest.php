<?php

declare(strict_types=1);

use App\Actions\Referees\UnretireAction;
use App\Exceptions\CannotBeUnretiredException;
use App\Models\Referee;
use App\Repositories\RefereeRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    Event::fake();

    testTime()->freeze();

    $this->refereeRepository = Mockery::mock(RefereeRepository::class);
});

test('it unretires a retired referee at the current datetime by default', function () {
    $referee = Referee::factory()->retired()->create();
    $datetime = now();

    $this->refereeRepository
        ->shouldReceive('unretire')
        ->once()
        ->withArgs(function (Referee $unretireReferee, Carbon $unretireDate) use ($referee, $datetime) {
            expect($unretireReferee->is($referee))->toBeTrue()
                ->and($unretireDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($referee);

    $this->refereeRepository
        ->shouldReceive('employ')
        ->once()
        ->withArgs(function (Referee $employableReferee, Carbon $unretireDate) use ($referee, $datetime) {
            expect($employableReferee->is($referee))->toBeTrue()
                ->and($unretireDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($referee);

    UnretireAction::run($referee);
});

test('it unretires a retired referee at a specific datetime', function () {
    $referee = Referee::factory()->retired()->create();
    $datetime = now()->addDays(2);

    $this->refereeRepository
        ->shouldReceive('unretire')
        ->once()
        ->with($referee, $datetime)
        ->andReturn($referee);

    $this->refereeRepository
        ->shouldReceive('employ')
        ->once()
        ->with($referee, $datetime)
        ->andReturn($referee);

    UnretireAction::run($referee, $datetime);
});

test('invoke throws exception for unretiring a non unretirable referee', function ($factoryState) {
    $referee = Referee::factory()->{$factoryState}()->create();

    UnretireAction::run($referee);
})->throws(CannotBeUnretiredException::class)->with([
    'bookable',
    'withFutureEmployment',
    'injured',
    'released',
    'suspended',
    'unemployed',
]);
