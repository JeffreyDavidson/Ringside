<?php

declare(strict_types=1);

use App\Actions\TagTeams\ReinstateAction;
use App\Exceptions\CannotBeReinstatedException;
use App\Models\TagTeam;
use App\Repositories\TagTeamRepository;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->tagTeamRepository = $this->mock(TagTeamRepository::class);
});

test('it reinstates a suspended tag team at the current datetime by default', function () {
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

    ReinstateAction::run($tagTeam);
});

test('it reinstates a suspended tag team at a specific datetime', function () {
    $tagTeam = TagTeam::factory()->suspended()->create();
    $datetime = now()->addDays(2);

    $this->tagTeamRepository
        ->shouldReceive('reinstate')
        ->once()
        ->with($tagTeam, $datetime)
        ->andReturns($tagTeam);

    ReinstateAction::run($tagTeam, $datetime);
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
])->skip();
