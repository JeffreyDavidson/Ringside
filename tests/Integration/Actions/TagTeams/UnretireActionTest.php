<?php

declare(strict_types=1);

use App\Actions\TagTeams\UnretireAction;
use App\Exceptions\Status\CannotBeUnretiredException;
use App\Models\TagTeams\TagTeam;
use App\Repositories\TagTeamRepository;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    Event::fake();

    testTime()->freeze();

    $this->tagTeamRepository = $this->mock(TagTeamRepository::class);
});

test('it unretires a retired tag team at the current datetime by default', function () {
    $tagTeam = TagTeam::factory()->retired()->create();
    $datetime = now();

    $this->tagTeamRepository
        ->shouldReceive('endRetirement')
        ->once()
        ->withArgs(function (TagTeam $unretirableTagTeam, Carbon $unretireDate) use ($tagTeam, $datetime) {
            expect($unretirableTagTeam->is($tagTeam))->toBeTrue()
                ->and($unretireDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    $this->tagTeamRepository
        ->shouldReceive('createEmployment')
        ->once()
        ->withArgs(function (TagTeam $employableTagTeam, Carbon $employmentDate) use ($tagTeam, $datetime) {
            expect($employableTagTeam->is($tagTeam))->toBeTrue()
                ->and($employmentDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    resolve(UnretireAction::class)->handle($tagTeam);
});

test('it unretires a retired tag team at a specific datetime', function () {
    $tagTeam = TagTeam::factory()->retired()->create();
    $datetime = now()->addDays(2);

    $this->tagTeamRepository
        ->shouldReceive('endRetirement')
        ->once()
        ->withArgs(function (TagTeam $unretirableTagTeam, Carbon $unretireDate) use ($tagTeam, $datetime) {
            expect($unretirableTagTeam->is($tagTeam))->toBeTrue()
                ->and($unretireDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    $this->tagTeamRepository
        ->shouldReceive('createEmployment')
        ->once()
        ->withArgs(function (TagTeam $employableTagTeam, Carbon $employmentDate) use ($tagTeam, $datetime) {
            expect($employableTagTeam->is($tagTeam))->toBeTrue()
                ->and($employmentDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    resolve(UnretireAction::class)->handle($tagTeam, $datetime);
});

test('invoke throws exception for unretiring a non unretirable tag team', function ($factoryState) {
    $tagTeam = TagTeam::factory()->{$factoryState}()->create();

    resolve(UnretireAction::class)->handle($tagTeam);
})->throws(CannotBeUnretiredException::class)->with([
    'bookable',
    'withFutureEmployment',
    'released',
    'suspended',
    'unemployed',
]);
