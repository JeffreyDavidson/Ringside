<?php

declare(strict_types=1);

use App\Actions\Referees\EmployAction;
use App\Exceptions\Status\CannotBeEmployedException;
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

test('referee employment status checks work correctly', function () {
    $unemployedReferee = Referee::factory()->unemployed()->create();
    $employedReferee = Referee::factory()->employed()->create();

    expect($unemployedReferee->canBeEmployed())->toBeTrue();
    expect($employedReferee->canBeEmployed())->toBeFalse();
});

test('it employs an employable referee at the current datetime by default', function ($factoryState) {
    $referee = Referee::factory()->{$factoryState}()->create();
    $datetime = now();

    $this->refereeRepository
        ->shouldReceive('createEmployment')
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
]);

test('it employs an employable referee at a specific datetime', function ($factoryState) {
    $referee = Referee::factory()->{$factoryState}()->create();
    $datetime = now()->addDays(2);

    $this->refereeRepository
        ->shouldReceive('createEmployment')
        ->once()
        ->with($referee, $datetime)
        ->andReturns($referee);

    resolve(EmployAction::class)->handle($referee, $datetime);
})->with([
    'unemployed',
    'released',
]);

test('it employs a retired referee at the current datetime by default', function () {
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

    resolve(EmployAction::class)->handle($referee);
});

test('it employs a retired referee at a specific datetime', function () {
    $referee = Referee::factory()->retired()->create();
    $datetime = now()->addDays(2);

    $this->refereeRepository
        ->shouldReceive('endRetirement')
        ->once()
        ->with(Mockery::any(), Mockery::any());

    $this->refereeRepository
        ->shouldReceive('createEmployment')
        ->once()
        ->with(Mockery::any(), Mockery::any());

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
