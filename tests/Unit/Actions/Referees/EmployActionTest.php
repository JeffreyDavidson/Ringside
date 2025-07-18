<?php

declare(strict_types=1);

use App\Actions\Referees\EmployAction;
use App\Exceptions\CannotBeEmployedException;
use App\Models\Referees\Referee;
use App\Repositories\RefereeRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    Event::fake();

    testTime()->freeze();

    $this->refereeRepository = $this->mock(RefereeRepository::class);
});

test('it employs an employable referee at the current datetime by default', function ($factoryState) {
    $referee = Referee::factory()->{$factoryState}()->create();
    $datetime = now();

    $this->refereeRepository
        ->shouldNotReceive('unretire');

    $this->refereeRepository
        ->shouldReceive('employ')
        ->once()
        ->withArgs(function (Referee $employableReferee, Carbon $employmentDate) use ($referee, $datetime) {
            expect($employableReferee->is($referee))->toBeTrue()
                ->and($employmentDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($referee);

    resolve(EmployAction::class)->handle($referee);
})->with([
    'unemployed',
    'released',
    'withFutureEmployment',
])->skip();

test('it employs an employable referee at a specific datetime', function ($factoryState) {
    $referee = Referee::factory()->{$factoryState}()->create();
    $datetime = now()->addDays(2);

    $this->refereeRepository
        ->shouldNotReceive('unretire');

    $this->refereeRepository
        ->shouldReceive('employ')
        ->once()
        ->with($referee, $datetime)
        ->andReturns($referee);

    resolve(EmployAction::class)->handle($referee, $datetime);
})->with([
    'unemployed',
    'released',
    'withFutureEmployment',
])->skip();

test('it employs a retired referee at the current datetime by default', function () {
    $referee = Referee::factory()->retired()->create();
    $datetime = now();

    $this->refereeRepository
        ->shouldReceive('unretire')
        ->withArgs(function (Referee $unretirableReferee, Carbon $unretireDate) use ($referee, $datetime) {
            expect($unretirableReferee->is($referee))->toBeTrue();
            expect($unretireDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->once()
        ->andReturn($referee);

    $this->refereeRepository
        ->shouldReceive('employ')
        ->once()
        ->withArgs(function (Referee $employedReferee, Carbon $employmentDate) use ($referee, $datetime) {
            expect($employedReferee->is($referee))->toBeTrue();
            expect($employmentDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($referee);

    resolve(EmployAction::class)->handle($referee);
});

test('it employs a retired referee at a specific datetime', function () {
    $referee = Referee::factory()->retired()->create();
    $datetime = now()->addDays(2);

    $this->refereeRepository
        ->shouldReceive('unretire')
        ->with($referee, $datetime)
        ->once()
        ->andReturn($referee);

    $this->refereeRepository
        ->shouldReceive('employ')
        ->once()
        ->with($referee, $datetime)
        ->andReturns($referee);

    resolve(EmployAction::class)->handle($referee, $datetime);
});

test('invoke employs a released referee and redirects', function ($factoryState) {
    $referee = Referee::factory()->{$factoryState}()->create();

    resolve(EmployAction::class)->handle($referee);
})->throws(CannotBeEmployedException::class)->with([
    'suspended',
    'injured',
    'bookable',
]);
