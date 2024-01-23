<?php

declare(strict_types=1);

use App\Actions\TagTeams\RetireAction;
use App\Exceptions\CannotBeRetiredException;
use App\Models\TagTeam;
use App\Repositories\TagTeamRepository;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->tagTeamRepository = Mockery::mock(TagTeamRepository::class);
});

test('it retires a bookable tag team at the current datetime by default', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();
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

    $this->tagTeamRepository
        ->shouldReceive('retire')
        ->once()
        ->withArgs(function (TagTeam $retirableTagTeam, Carbon $retirementDate) use ($tagTeam, $datetime) {
            expect($retirableTagTeam->is($tagTeam))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    RetireAction::run($tagTeam);
});

test('it retires a bookable tag team at a specific datetime', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();
    $datetime = now()->addDays(2);

    $this->tagTeamRepository
        ->shouldNotReceive('reinstate');

    $this->tagTeamRepository
        ->shouldReceive('release')
        ->once()
        ->with($tagTeam, $datetime)
        ->andReturns($tagTeam);

    $this->tagTeamRepository
        ->shouldReceive('retire')
        ->once()
        ->with($tagTeam, $datetime)
        ->andReturns($tagTeam);

    RetireAction::run($tagTeam, $datetime);
});

test('it retires a released tag team at the current datetime by default', function () {
    $tagTeam = TagTeam::factory()->released()->create();
    $datetime = now();

    $this->tagTeamRepository
        ->shouldNotReceive('reinstate');

    $this->tagTeamRepository
        ->shouldNotReceive('release');

    $this->tagTeamRepository
        ->shouldReceive('retire')
        ->once()
        ->withArgs(function (TagTeam $retirableTagTeam, Carbon $retirementDate) use ($tagTeam, $datetime) {
            expect($retirableTagTeam->is($tagTeam))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    RetireAction::run($tagTeam);
});

test('it retires a released tag team at a specific datetime', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();
    $datetime = now()->addDays(2);

    $this->tagTeamRepository
        ->shouldNotReceive('reinstate');

    $this->tagTeamRepository
        ->shouldReceive('release')
        ->once()
        ->with($tagTeam, $datetime)
        ->andReturns($tagTeam);

    $this->tagTeamRepository
        ->shouldReceive('retire')
        ->once()
        ->with($tagTeam, $datetime)
        ->andReturns($tagTeam);

    RetireAction::run($tagTeam, $datetime);
});

test('it retires a suspended tag team at the current datetime by default', function () {
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

    $this->tagTeamRepository
        ->shouldReceive('retire')
        ->once()
        ->withArgs(function (TagTeam $retirableTagTeam, Carbon $retirementDate) use ($tagTeam, $datetime) {
            expect($retirableTagTeam->is($tagTeam))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    RetireAction::run($tagTeam);
});

test('it retires a suspended tag team at a specific datetime', function () {
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

    $this->tagTeamRepository
        ->shouldReceive('retire')
        ->once()
        ->with($tagTeam, $datetime)
        ->andReturns($tagTeam);

    RetireAction::run($tagTeam, $datetime);
});

test('it retires an unbookable tag team at the current datetime by default', function () {
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

    $this->tagTeamRepository
        ->shouldReceive('retire')
        ->once()
        ->withArgs(function (TagTeam $retirableTagTeam, Carbon $retirementDate) use ($tagTeam, $datetime) {
            expect($retirableTagTeam->is($tagTeam))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    RetireAction::run($tagTeam);
});

test('it retires an unbookable tag team at a specific datetime', function () {
    $tagTeam = TagTeam::factory()->unbookable()->create();
    $datetime = now()->addDays(2);

    $this->tagTeamRepository
        ->shouldNotReceive('reinstate');

    $this->tagTeamRepository
        ->shouldReceive('release')
        ->once()
        ->with($tagTeam, $datetime)
        ->andReturns($tagTeam);

    $this->tagTeamRepository
        ->shouldReceive('retire')
        ->once()
        ->with($tagTeam, $datetime)
        ->andReturns($tagTeam);

    RetireAction::run($tagTeam, $datetime);
});

test('it throws exception for retiring a non retirable tag team', function ($factoryState) {
    $tagTeam = TagTeam::factory()->{$factoryState}()->create();

    RetireAction::run($tagTeam);
})->throws(CannotBeRetiredException::class)->with([
    'retired',
    'withFutureEmployment',
    'unemployed',
]);
