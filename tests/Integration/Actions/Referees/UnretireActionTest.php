<?php

declare(strict_types=1);

use App\Actions\Referees\UnretireAction;
use App\Exceptions\Status\CannotBeUnretiredException;
use App\Models\Referees\Referee;
use App\Repositories\RefereeRepository;
use Illuminate\Support\Facades\Event;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    Event::fake();

    testTime()->freeze();

    $this->refereeRepository = $this->mock(RefereeRepository::class);
});

test('it unretires a retired referee at the current datetime by default', function () {
    $referee = Referee::factory()->retired()->create();
    $datetime = now();

    $this->refereeRepository
        ->shouldReceive('endRetirement')
        ->once()
        ->with(Mockery::any(), Mockery::any());

    $this->refereeRepository
        ->shouldReceive('createEmployment')
        ->once()
        ->with(Mockery::any(), Mockery::any());

    resolve(UnretireAction::class)->handle($referee);
});

test('it unretires a retired referee at a specific datetime', function () {
    $referee = Referee::factory()->retired()->create();
    $datetime = now()->addDays(2);

    $this->refereeRepository
        ->shouldReceive('endRetirement')
        ->once()
        ->with($referee, $datetime);

    $this->refereeRepository
        ->shouldReceive('createEmployment')
        ->once()
        ->with($referee, $datetime);

    resolve(UnretireAction::class)->handle($referee, $datetime);
});

test('invoke throws exception for unretiring a non unretirable referee', function ($factoryState) {
    $referee = Referee::factory()->{$factoryState}()->create();

    resolve(UnretireAction::class)->handle($referee);
})->throws(CannotBeUnretiredException::class)->with([
    'bookable',
    'withFutureEmployment',
    'injured',
    'released',
    'suspended',
    'unemployed',
]);
