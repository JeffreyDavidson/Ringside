<?php

declare(strict_types=1);

use App\Actions\TagTeams\SuspendAction;
use App\Exceptions\CannotBeSuspendedException;
use App\Models\TagTeam;
use App\Models\Wrestler;
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
        ->shouldReceive('suspend')
        ->once()
        ->withArgs(function (TagTeam $suspendableTagTeam, Carbon $suspensionDate) use ($tagTeam, $datetime) {
            expect($suspendableTagTeam->is($tagTeam))->toBeTrue()
                ->and($suspensionDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($tagTeam);

    SuspendAction::run($tagTeam);
});

test('it suspends a bookable tag team at a specific datetime', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();
    $datetime = now()->addDays(2);

    $this->tagTeamRepository
        ->shouldReceive('suspend')
        ->once()
        ->with($tagTeam, $datetime)
        ->andReturns($tagTeam);

    SuspendAction::run($tagTeam, $datetime);
});

test('invoke throws exception for retiring a non retirable tag team', function ($factoryState) {
    $tagTeam = TagTeam::factory()->{$factoryState}()->create();

    SuspendAction::run($tagTeam);
})->throws(CannotBeSuspendedException::class)->with([
    'unemployed',
    'released',
    'withFutureEmployment',
    'retired',
    'unbookable',
    'suspended',
]);
