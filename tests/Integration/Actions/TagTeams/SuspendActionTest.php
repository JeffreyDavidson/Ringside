<?php

declare(strict_types=1);

use App\Actions\TagTeams\SuspendAction;
use App\Exceptions\Status\CannotBeSuspendedException;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\TagTeamRepository;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->tagTeamRepository = $this->mock(TagTeamRepository::class);
});

test('it suspends a bookable tag team at the current datetime by default', function () {
    [$wrestlerA, $wrestlerB] = Wrestler::factory()->bookable()->count(2)->create();
    $tagTeam = TagTeam::factory()
        ->hasAttached($wrestlerA, ['joined_at' => now()->toDateTimeString()])
        ->hasAttached($wrestlerB, ['joined_at' => now()->toDateTimeString()])
        ->bookable()
        ->create();
    $datetime = now();

    $this->tagTeamRepository
        ->shouldReceive('createSuspension')
        ->once()
        ->withArgs(function (TagTeam $suspendableTagTeam, Carbon $suspensionDate) use ($tagTeam, $datetime) {
            expect($suspendableTagTeam->is($tagTeam))->toBeTrue()
                ->and($suspensionDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    resolve(SuspendAction::class)->handle($tagTeam);
});

test('it suspends a bookable tag team at a specific datetime', function () {
    [$wrestlerA, $wrestlerB] = Wrestler::factory()->bookable()->count(2)->create();
    $tagTeam = TagTeam::factory()
        ->hasAttached($wrestlerA, ['joined_at' => now()->toDateTimeString()])
        ->hasAttached($wrestlerB, ['joined_at' => now()->toDateTimeString()])
        ->bookable()
        ->create();
    $datetime = now()->addDays(2);

    $this->tagTeamRepository
        ->shouldReceive('createSuspension')
        ->once()
        ->withArgs(function (TagTeam $suspendableTagTeam, Carbon $suspensionDate) use ($tagTeam, $datetime) {
            expect($suspendableTagTeam->is($tagTeam))->toBeTrue()
                ->and($suspensionDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    resolve(SuspendAction::class)->handle($tagTeam, $datetime);
});

test('invoke throws exception for retiring a non retirable tag team', function ($factoryState) {
    $tagTeam = TagTeam::factory()->{$factoryState}()->create();

    resolve(SuspendAction::class)->handle($tagTeam);
})->throws(CannotBeSuspendedException::class)->with([
    'unemployed',
    'released',
    'withFutureEmployment',
    'retired',
    'unbookable',
    'suspended',
]);
