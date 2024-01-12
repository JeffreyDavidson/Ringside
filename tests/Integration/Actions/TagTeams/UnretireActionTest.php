<?php

declare(strict_types=1);

use App\Actions\TagTeams\UnretireAction;
use App\Exceptions\CannotBeUnretiredException;
use App\Models\TagTeam;
use App\Repositories\TagTeamRepository;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    Event::fake();

    testTime()->freeze();

    $this->tagTeamRepository = Mockery::mock(TagTeamRepository::class);
});

test('it unretires a retired tag team at the current datetime by default', function () {
    $tagTeam = TagTeam::factory()->retired()->create();
    $datetime = now();

    $this->tagTeamRepository
        ->shouldReceive('unretire')
        ->once()
        ->withArgs(function (TagTeam $unretirableTagTeam, Carbon $unretireDate) use ($tagTeam, $datetime) {
            expect($unretirableTagTeam->is($tagTeam))->toBeTrue()
                ->and($unretireDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    $this->tagTeamRepository
        ->shouldReceive('employ')
        ->once()
        ->withArgs(function (TagTeam $employableTagTeam, Carbon $employmentDate) use ($tagTeam, $datetime) {
            expect($employableTagTeam->is($tagTeam))->toBeTrue()
                ->and($employmentDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    UnretireAction::run($tagTeam);
});

test('it unretires a retired tag team at a specific datetime', function () {
    $tagTeam = TagTeam::factory()->retired()->create();
    $datetime = now()->addDays(2);

    $this->tagTeamRepository
        ->shouldReceive('unretire')
        ->once()
        ->with($tagTeam, $datetime)
        ->andReturns($tagTeam);

    $this->tagTeamRepository
        ->shouldReceive('employ')
        ->once()
        ->with($tagTeam, $datetime)
        ->andReturns($tagTeam);

    UnretireAction::run($tagTeam, $datetime);
});

test('invoke throws exception for unretiring a non unretirable tag team', function ($factoryState) {
    $tagTeam = TagTeam::factory()->{$factoryState}()->create();

    UnretireAction::run($tagTeam);
})->throws(CannotBeUnretiredException::class)->with([
    'bookable',
    'withFutureEmployment',
    'released',
    'suspended',
    'unemployed',
]);
