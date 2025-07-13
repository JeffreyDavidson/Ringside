<?php

declare(strict_types=1);

use App\Actions\TagTeams\RetireAction;
use App\Exceptions\Status\CannotBeRetiredException;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\TagTeamRepository;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->tagTeamRepository = $this->mock(TagTeamRepository::class);
});

test('it retires a bookable tag team at the current datetime by default', function () {
    [$wrestlerA, $wrestlerB] = Wrestler::factory()->bookable()->count(2)->create();
    $tagTeam = TagTeam::factory()
        ->hasAttached($wrestlerA, ['joined_at' => now()->toDateTimeString()])
        ->hasAttached($wrestlerB, ['joined_at' => now()->toDateTimeString()])
        ->bookable()
        ->create();
    $datetime = now();

    $this->tagTeamRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->withArgs(function (TagTeam $retirableTagTeam, Carbon $retirementDate) use ($tagTeam, $datetime) {
            expect($retirableTagTeam->is($tagTeam))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    resolve(RetireAction::class)->handle($tagTeam);
});

test('it retires a bookable tag team at a specific datetime', function () {
    [$wrestlerA, $wrestlerB] = Wrestler::factory()->bookable()->count(2)->create();
    $tagTeam = TagTeam::factory()
        ->hasAttached($wrestlerA, ['joined_at' => now()->toDateTimeString()])
        ->hasAttached($wrestlerB, ['joined_at' => now()->toDateTimeString()])
        ->bookable()
        ->create();
    $datetime = now()->addDays(2);

    $this->tagTeamRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->withArgs(function (TagTeam $retirableTagTeam, Carbon $retirementDate) use ($tagTeam, $datetime) {
            expect($retirableTagTeam->is($tagTeam))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    resolve(RetireAction::class)->handle($tagTeam, $datetime);
});

test('it retires a released tag team at the current datetime by default', function () {
    [$wrestlerA, $wrestlerB] = Wrestler::factory()->released()->count(2)->create();
    $tagTeam = TagTeam::factory()
        ->hasAttached($wrestlerA, ['joined_at' => now()->toDateTimeString()])
        ->hasAttached($wrestlerB, ['joined_at' => now()->toDateTimeString()])
        ->released()
        ->create();
    $datetime = now();

    $this->tagTeamRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->withArgs(function (TagTeam $retirableTagTeam, Carbon $retirementDate) use ($tagTeam, $datetime) {
            expect($retirableTagTeam->is($tagTeam))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    resolve(RetireAction::class)->handle($tagTeam);
});

test('it retires a released tag team at a specific datetime', function () {
    [$wrestlerA, $wrestlerB] = Wrestler::factory()->released()->count(2)->create();
    $tagTeam = TagTeam::factory()
        ->hasAttached($wrestlerA, ['joined_at' => now()->toDateTimeString()])
        ->hasAttached($wrestlerB, ['joined_at' => now()->toDateTimeString()])
        ->released()
        ->create();
    $datetime = now()->addDays(2);

    $this->tagTeamRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->withArgs(function (TagTeam $retirableTagTeam, Carbon $retirementDate) use ($tagTeam, $datetime) {
            expect($retirableTagTeam->is($tagTeam))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    resolve(RetireAction::class)->handle($tagTeam, $datetime);
});

test('it retires a suspended tag team at the current datetime by default', function () {
    [$wrestlerA, $wrestlerB] = Wrestler::factory()->bookable()->count(2)->create();
    $tagTeam = TagTeam::factory()
        ->hasAttached($wrestlerA, ['joined_at' => now()->toDateTimeString()])
        ->hasAttached($wrestlerB, ['joined_at' => now()->toDateTimeString()])
        ->suspended()
        ->create();
    $datetime = now();

    $this->tagTeamRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->withArgs(function (TagTeam $retirableTagTeam, Carbon $retirementDate) use ($tagTeam, $datetime) {
            expect($retirableTagTeam->is($tagTeam))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    resolve(RetireAction::class)->handle($tagTeam);
});

test('it retires a suspended tag team at a specific datetime', function () {
    [$wrestlerA, $wrestlerB] = Wrestler::factory()->bookable()->count(2)->create();
    $tagTeam = TagTeam::factory()
        ->hasAttached($wrestlerA, ['joined_at' => now()->toDateTimeString()])
        ->hasAttached($wrestlerB, ['joined_at' => now()->toDateTimeString()])
        ->suspended()
        ->create();
    $datetime = now()->addDays(2);

    $this->tagTeamRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->withArgs(function (TagTeam $retirableTagTeam, Carbon $retirementDate) use ($tagTeam, $datetime) {
            expect($retirableTagTeam->is($tagTeam))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    resolve(RetireAction::class)->handle($tagTeam, $datetime);
});

test('it retires an unbookable tag team at the current datetime by default', function () {
    [$wrestlerA, $wrestlerB] = Wrestler::factory()->bookable()->count(2)->create();
    $tagTeam = TagTeam::factory()
        ->hasAttached($wrestlerA, ['joined_at' => now()->toDateTimeString()])
        ->hasAttached($wrestlerB, ['joined_at' => now()->toDateTimeString()])
        ->bookable()
        ->create();
    $datetime = now();

    $this->tagTeamRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->withArgs(function (TagTeam $retirableTagTeam, Carbon $retirementDate) use ($tagTeam, $datetime) {
            expect($retirableTagTeam->is($tagTeam))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    resolve(RetireAction::class)->handle($tagTeam);
});

test('it retires an unbookable tag team at a specific datetime', function () {
    [$wrestlerA, $wrestlerB] = Wrestler::factory()->bookable()->count(2)->create();
    $tagTeam = TagTeam::factory()
        ->hasAttached($wrestlerA, ['joined_at' => now()->toDateTimeString()])
        ->hasAttached($wrestlerB, ['joined_at' => now()->toDateTimeString()])
        ->bookable()
        ->create();
    $datetime = now()->addDays(2);

    $this->tagTeamRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->withArgs(function (TagTeam $retirableTagTeam, Carbon $retirementDate) use ($tagTeam, $datetime) {
            expect($retirableTagTeam->is($tagTeam))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    resolve(RetireAction::class)->handle($tagTeam, $datetime);
});

test('it throws exception for retiring a non retirable tag team', function ($factoryState) {
    $tagTeam = TagTeam::factory()->{$factoryState}()->create();

    resolve(RetireAction::class)->handle($tagTeam);
})->throws(CannotBeRetiredException::class)->with([
    'retired',
    'withFutureEmployment',
    'unemployed',
]);
