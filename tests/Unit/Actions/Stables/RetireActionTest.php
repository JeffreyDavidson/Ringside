<?php

declare(strict_types=1);

use App\Actions\Managers\RetireAction as ManagerRetireAction;
use App\Actions\Stables\RetireAction;
use App\Actions\TagTeams\RetireAction as TagTeamRetireAction;
use App\Actions\Wrestlers\RetireAction as WrestlerRetireAction;
use App\Exceptions\Status\CannotBeRetiredException;
use App\Models\Managers\Manager;
use App\Models\Stables\Stable;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\StableRepository;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->stableRepository = $this->mock(StableRepository::class);
});

test('it retires an active stable at the current datetime by default', function () {
    $stable = Stable::factory()->active()->create();
    $datetime = now();

    $this->stableRepository
        ->shouldReceive('endActivity')
        ->once()
        ->withArgs(function (Stable $retirableStable, Carbon $retirementDate) use ($stable, $datetime) {
            expect($retirableStable->is($stable))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($stable);

    $this->stableRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->withArgs(function (Stable $retirableStable, Carbon $retirementDate) use ($stable, $datetime) {
            expect($retirableStable->is($stable))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($stable);

    $this->stableRepository
        ->shouldReceive('removeWrestlers')
        ->once();

    $this->stableRepository
        ->shouldReceive('removeTagTeams')
        ->once();

    $this->stableRepository
        ->shouldReceive('removeWrestler')
        ->zeroOrMoreTimes();

    resolve(RetireAction::class)->handle($stable);
});

test('it retires an active stable at a specific datetime', function () {
    $stable = Stable::factory()->active()->create();
    $datetime = now()->addDays(2);

    $this->stableRepository
        ->shouldReceive('endActivity')
        ->once()
        ->with($stable, $datetime)
        ->andReturns($stable);

    $this->stableRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->with($stable, $datetime)
        ->andReturns($stable);

    $this->stableRepository
        ->shouldReceive('removeWrestlers')
        ->once();

    $this->stableRepository
        ->shouldReceive('removeTagTeams')
        ->once();

    $this->stableRepository
        ->shouldReceive('removeWrestler')
        ->zeroOrMoreTimes();

    resolve(RetireAction::class)->handle($stable, $datetime);
});

test('it retires an inactive stable at the current datetime by default', function () {
    $stable = Stable::factory()->inactive()->create();
    $datetime = now();

    $this->stableRepository
        ->shouldNotReceive('endActivity');

    $this->stableRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->withArgs(function (Stable $retirableStable, Carbon $retirementDate) use ($stable, $datetime) {
            expect($retirableStable->is($stable))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($stable);

    $this->stableRepository
        ->shouldReceive('removeWrestlers')
        ->once();

    $this->stableRepository
        ->shouldReceive('removeTagTeams')
        ->once();

    $this->stableRepository
        ->shouldReceive('removeWrestler')
        ->zeroOrMoreTimes();

    resolve(RetireAction::class)->handle($stable);
});

test('it retires an inactive stable at a specific datetime', function () {
    $stable = Stable::factory()->inactive()->create();
    $datetime = now()->addDays(2);

    $this->stableRepository
        ->shouldNotReceive('endActivity');

    $this->stableRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->with($stable, $datetime)
        ->andReturns($stable);

    $this->stableRepository
        ->shouldReceive('removeWrestlers')
        ->once();

    $this->stableRepository
        ->shouldReceive('removeTagTeams')
        ->once();

    $this->stableRepository
        ->shouldReceive('removeWrestler')
        ->zeroOrMoreTimes();

    resolve(RetireAction::class)->handle($stable, $datetime);
});

test('it retires the current tag teams and current wrestlers of a stable', function () {
    // Create an active stable (which creates its own wrestlers and tag teams)
    $stable = Stable::factory()->active()->create();
    $datetime = now();

    // Count how many current members there are for the expectations
    $currentWrestlersCount = $stable->currentWrestlers()->count();
    $currentTagTeamsCount = $stable->currentTagTeams()->count();

    $this->stableRepository
        ->shouldReceive('endActivity')
        ->once()
        ->with($stable, $datetime)
        ->andReturns($stable);

    $this->stableRepository
        ->shouldReceive('removeWrestlers')
        ->once()
        ->with($stable, $stable->currentWrestlers, $datetime);

    $this->stableRepository
        ->shouldReceive('removeTagTeams')
        ->once()
        ->with($stable, $stable->currentTagTeams, $datetime);

    $this->stableRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->with($stable, $datetime)
        ->andReturns($stable);

    TagTeamRetireAction::shouldRun()->times($currentTagTeamsCount);
    WrestlerRetireAction::shouldRun()->times($currentWrestlersCount);

    resolve(RetireAction::class)->handle($stable, $datetime);
});

test('it throws exception trying to retire a non retirable stable', function ($factoryState) {
    $stable = Stable::factory()->{$factoryState}()->create();

    resolve(RetireAction::class)->handle($stable);
})->throws(CannotBeRetiredException::class)->with([
    'unactivated',
    'withFutureActivation',
    'retired',
]);
