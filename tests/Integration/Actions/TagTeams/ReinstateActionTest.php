<?php

declare(strict_types=1);

use App\Actions\TagTeams\ReinstateAction;
use App\Exceptions\Status\CannotBeReinstatedException;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\TagTeamRepository;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->tagTeamRepository = $this->mock(TagTeamRepository::class);
});

test('it reinstates a suspended tag team at the current datetime by default', function () {
    [$wrestlerA, $wrestlerB] = Wrestler::factory()->suspended()->count(2)->create();
    $tagTeam = TagTeam::factory()
        ->hasAttached($wrestlerA, ['joined_at' => now()->toDateTimeString()])
        ->hasAttached($wrestlerB, ['joined_at' => now()->toDateTimeString()])
        ->suspended()
        ->create();
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

    resolve(ReinstateAction::class)->handle($tagTeam);
});

test('it reinstates a suspended tag team at a specific datetime', function () {
    [$wrestlerA, $wrestlerB] = Wrestler::factory()->suspended()->count(2)->create();
    $tagTeam = TagTeam::factory()
        ->hasAttached($wrestlerA, ['joined_at' => now()->toDateTimeString()])
        ->hasAttached($wrestlerB, ['joined_at' => now()->toDateTimeString()])
        ->suspended()
        ->create();
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

    resolve(ReinstateAction::class)->handle($tagTeam, $datetime);
});

test('it throws exception for reinstating a non reinstatable tag team', function ($factoryState) {
    $tagTeam = TagTeam::factory()->{$factoryState}()->create();

    ReinstateAction::run($tagTeam);
})->throws(CannotBeReinstatedException::class)->with([
    'bookable',
    'withFutureEmployment',
    'unemployed',
    'released',
    'retired',
    'unbookable',
]);
