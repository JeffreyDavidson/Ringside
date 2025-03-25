<?php

declare(strict_types=1);

use App\Actions\TagTeams\ReleaseAction;
use App\Exceptions\CannotBeReleasedException;
use App\Models\TagTeam;
use App\Repositories\TagTeamRepository;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->tagTeamRepository = $this->mock(TagTeamRepository::class);
});

test('it releases a bookable tag team at the current datetime by default', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();
    $datetime = now();

    $this->tagTeamRepository
        ->shouldReceive('release')
        ->once()
        ->withArgs(function (TagTeam $releasableTagTeam, Carbon $releaseDate) use ($tagTeam, $datetime) {
            expect($releasableTagTeam->is($tagTeam))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    ReleaseAction::run($tagTeam);
});

test('it releases a bookable tag team at a specific datetime', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();
    $datetime = now()->addDays(2);

    $this->tagTeamRepository
        ->shouldReceive('release')
        ->once()
        ->with($tagTeam, $datetime)
        ->andReturns($tagTeam);

    ReleaseAction::run($tagTeam, $datetime);
});

test('it releases a suspended tag team at the current datetime by default', function () {
    $tagTeam = TagTeam::factory()->suspended()->create();
    $datetime = now();

    $this->tagTeamRepository
        ->shouldReceive('reinstate')
        ->once()
        ->withArgs(function (TagTeam $reinstatableTagTeam, Carbon $reinstatementDate) use ($tagTeam, $datetime) {
            expect($reinstatableTagTeam->is($tagTeam))->toBeTrue()
                ->and($reinstatementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    $this->tagTeamRepository
        ->shouldReceive('release')
        ->once()
        ->withArgs(function (TagTeam $releasableTagTeam, Carbon $releaseDate) use ($tagTeam, $datetime) {
            expect($releasableTagTeam->is($tagTeam))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    ReleaseAction::run($tagTeam);
});

test('it releases a suspended tag team at a specific datetime', function () {
    $tagTeam = TagTeam::factory()->suspended()->create();
    $datetime = now()->addDays(2);

    $this->tagTeamRepository
        ->shouldReceive('reinstate')
        ->once()
        ->with($tagTeam, $datetime)
        ->andReturns($tagTeam);

    $this->tagTeamRepository
        ->shouldReceive('release')
        ->once()
        ->with($tagTeam, $datetime)
        ->andReturns($tagTeam);

    ReleaseAction::run($tagTeam, $datetime);
});

test('it releases an unbookable tag team at the current datetime by default', function () {
    $tagTeam = TagTeam::factory()->unbookable()->create();
    $datetime = now();

    $this->tagTeamRepository
        ->shouldNotReceive('reinstate');

    $this->tagTeamRepository
        ->shouldReceive('release')
        ->once()
        ->withArgs(function (TagTeam $releasableTagTeam, Carbon $releaseDate) use ($tagTeam, $datetime) {
            expect($releasableTagTeam->is($tagTeam))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    ReleaseAction::run($tagTeam);
});

test('it releases an unbookable tag team at a specific datetime', function () {
    $tagTeam = TagTeam::factory()->unbookable()->create();
    $datetime = now()->addDays(2);

    $this->tagTeamRepository
        ->shouldNotReceive('reinstate');

    $this->tagTeamRepository
        ->shouldReceive('release')
        ->once()
        ->with($tagTeam, $datetime)
        ->andReturns($tagTeam);

    ReleaseAction::run($tagTeam, $datetime);
});

test('it throws an exception for releasing a non releasable tag team', function ($factoryState) {
    $tagTeam = TagTeam::factory()->{$factoryState}()->create();

    ReleaseAction::run($tagTeam);
})->throws(CannotBeReleasedException::class)->with([
    'unemployed',
    'withFutureEmployment',
    'released',
    'retired',
]);
