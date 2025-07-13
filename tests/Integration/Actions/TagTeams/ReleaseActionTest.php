<?php

declare(strict_types=1);

use App\Actions\TagTeams\ReleaseAction;
use App\Exceptions\Status\CannotBeReleasedException;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
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
        ->shouldReceive('removeWrestlers')
        ->once()
        ->withArgs(function (TagTeam $releasableTagTeam, $wrestlers, Carbon $releaseDate) use ($tagTeam, $datetime) {
            expect($releasableTagTeam->is($tagTeam))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    $this->tagTeamRepository
        ->shouldReceive('removeManagers')
        ->once()
        ->withArgs(function (TagTeam $releasableTagTeam, $managers, Carbon $releaseDate) use ($tagTeam, $datetime) {
            expect($releasableTagTeam->is($tagTeam))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    $this->tagTeamRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->withArgs(function (TagTeam $releasableTagTeam, Carbon $releaseDate) use ($tagTeam, $datetime) {
            expect($releasableTagTeam->is($tagTeam))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    resolve(ReleaseAction::class)->handle($tagTeam);
});

test('it releases a bookable tag team at a specific datetime', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();
    $datetime = now()->addDays(2);

    $this->tagTeamRepository
        ->shouldReceive('removeWrestlers')
        ->once()
        ->withArgs(function (TagTeam $releasableTagTeam, $wrestlers, Carbon $releaseDate) use ($tagTeam, $datetime) {
            expect($releasableTagTeam->is($tagTeam))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    $this->tagTeamRepository
        ->shouldReceive('removeManagers')
        ->once()
        ->withArgs(function (TagTeam $releasableTagTeam, $managers, Carbon $releaseDate) use ($tagTeam, $datetime) {
            expect($releasableTagTeam->is($tagTeam))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    $this->tagTeamRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->withArgs(function (TagTeam $releasableTagTeam, Carbon $releaseDate) use ($tagTeam, $datetime) {
            expect($releasableTagTeam->is($tagTeam))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    resolve(ReleaseAction::class)->handle($tagTeam, $datetime);
});

test('it releases a suspended tag team at the current datetime by default', function () {
    $tagTeam = TagTeam::factory()->suspended()->create();
    $datetime = now();

    $this->tagTeamRepository
        ->shouldReceive('endSuspension')
        ->once()
        ->withArgs(function (TagTeam $reinstatableTagTeam, Carbon $reinstatementDate) use ($tagTeam, $datetime) {
            expect($reinstatableTagTeam->is($tagTeam))->toBeTrue()
                ->and($reinstatementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    $this->tagTeamRepository
        ->shouldReceive('removeWrestlers')
        ->once()
        ->withArgs(function (TagTeam $releasableTagTeam, $wrestlers, Carbon $releaseDate) use ($tagTeam, $datetime) {
            expect($releasableTagTeam->is($tagTeam))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    $this->tagTeamRepository
        ->shouldReceive('removeManagers')
        ->once()
        ->withArgs(function (TagTeam $releasableTagTeam, $managers, Carbon $releaseDate) use ($tagTeam, $datetime) {
            expect($releasableTagTeam->is($tagTeam))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    $this->tagTeamRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->withArgs(function (TagTeam $releasableTagTeam, Carbon $releaseDate) use ($tagTeam, $datetime) {
            expect($releasableTagTeam->is($tagTeam))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    resolve(ReleaseAction::class)->handle($tagTeam);
});

test('it releases a suspended tag team at a specific datetime', function () {
    $tagTeam = TagTeam::factory()->suspended()->create();
    $datetime = now()->addDays(2);

    $this->tagTeamRepository
        ->shouldReceive('endSuspension')
        ->once()
        ->withArgs(function (TagTeam $reinstatableTagTeam, Carbon $reinstatementDate) use ($tagTeam, $datetime) {
            expect($reinstatableTagTeam->is($tagTeam))->toBeTrue()
                ->and($reinstatementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    $this->tagTeamRepository
        ->shouldReceive('removeWrestlers')
        ->once()
        ->withArgs(function (TagTeam $releasableTagTeam, $wrestlers, Carbon $releaseDate) use ($tagTeam, $datetime) {
            expect($releasableTagTeam->is($tagTeam))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    $this->tagTeamRepository
        ->shouldReceive('removeManagers')
        ->once()
        ->withArgs(function (TagTeam $releasableTagTeam, $managers, Carbon $releaseDate) use ($tagTeam, $datetime) {
            expect($releasableTagTeam->is($tagTeam))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    $this->tagTeamRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->withArgs(function (TagTeam $releasableTagTeam, Carbon $releaseDate) use ($tagTeam, $datetime) {
            expect($releasableTagTeam->is($tagTeam))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    resolve(ReleaseAction::class)->handle($tagTeam, $datetime);
});

test('it releases an unbookable tag team at the current datetime by default', function () {
    [$wrestlerA, $wrestlerB] = Wrestler::factory()->bookable()->count(2)->create();
    $tagTeam = TagTeam::factory()
        ->hasAttached($wrestlerA, ['joined_at' => now()->toDateTimeString()])
        ->hasAttached($wrestlerB, ['joined_at' => now()->toDateTimeString()])
        ->bookable()
        ->create();
    $datetime = now();

    $this->tagTeamRepository
        ->shouldNotReceive('endSuspension');

    $this->tagTeamRepository
        ->shouldReceive('removeWrestlers')
        ->once()
        ->withArgs(function (TagTeam $releasableTagTeam, $wrestlers, Carbon $releaseDate) use ($tagTeam, $datetime) {
            expect($releasableTagTeam->is($tagTeam))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    $this->tagTeamRepository
        ->shouldReceive('removeManagers')
        ->once()
        ->withArgs(function (TagTeam $releasableTagTeam, $managers, Carbon $releaseDate) use ($tagTeam, $datetime) {
            expect($releasableTagTeam->is($tagTeam))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    $this->tagTeamRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->withArgs(function (TagTeam $releasableTagTeam, Carbon $releaseDate) use ($tagTeam, $datetime) {
            expect($releasableTagTeam->is($tagTeam))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    resolve(ReleaseAction::class)->handle($tagTeam);
});

test('it releases an unbookable tag team at a specific datetime', function () {
    [$wrestlerA, $wrestlerB] = Wrestler::factory()->bookable()->count(2)->create();
    $tagTeam = TagTeam::factory()
        ->hasAttached($wrestlerA, ['joined_at' => now()->toDateTimeString()])
        ->hasAttached($wrestlerB, ['joined_at' => now()->toDateTimeString()])
        ->bookable()
        ->create();
    $datetime = now()->addDays(2);

    $this->tagTeamRepository
        ->shouldNotReceive('endSuspension');

    $this->tagTeamRepository
        ->shouldReceive('removeWrestlers')
        ->once()
        ->withArgs(function (TagTeam $releasableTagTeam, $wrestlers, Carbon $releaseDate) use ($tagTeam, $datetime) {
            expect($releasableTagTeam->is($tagTeam))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    $this->tagTeamRepository
        ->shouldReceive('removeManagers')
        ->once()
        ->withArgs(function (TagTeam $releasableTagTeam, $managers, Carbon $releaseDate) use ($tagTeam, $datetime) {
            expect($releasableTagTeam->is($tagTeam))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    $this->tagTeamRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->withArgs(function (TagTeam $releasableTagTeam, Carbon $releaseDate) use ($tagTeam, $datetime) {
            expect($releasableTagTeam->is($tagTeam))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    resolve(ReleaseAction::class)->handle($tagTeam, $datetime);
});

test('it throws an exception for releasing a non releasable tag team', function ($factoryState) {
    $tagTeam = TagTeam::factory()->{$factoryState}()->create();

    resolve(ReleaseAction::class)->handle($tagTeam);
})->throws(CannotBeReleasedException::class)->with([
    'unemployed',
    'withFutureEmployment',
    'released',
    'retired',
]);
