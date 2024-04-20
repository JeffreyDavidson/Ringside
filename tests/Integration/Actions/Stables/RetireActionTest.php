<?php

declare(strict_types=1);

use App\Actions\Managers\RetireAction as ManagerRetireAction;
use App\Actions\Stables\RetireAction;
use App\Actions\TagTeams\RetireAction as TagTeamRetireAction;
use App\Actions\Wrestlers\RetireAction as WrestlerRetireAction;
use App\Exceptions\CannotBeRetiredException;
use App\Models\Manager;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
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
        ->shouldReceive('deactivate')
        ->once()
        ->withArgs(function (Stable $retirableStable, Carbon $retirementDate) use ($stable, $datetime) {
            expect($retirableStable->is($stable))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($stable);

    $this->stableRepository
        ->shouldReceive('retire')
        ->once()
        ->withArgs(function (Stable $retirableStable, Carbon $retirementDate) use ($stable, $datetime) {
            expect($retirableStable->is($stable))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($stable);

    RetireAction::run($stable);
});

test('it retires an active stable at a specific datetime', function () {
    $stable = Stable::factory()->active()->create();
    $datetime = now()->addDays(2);

    $this->stableRepository
        ->shouldReceive('deactivate')
        ->once()
        ->with($stable, $datetime)
        ->andReturns($stable);

    $this->stableRepository
        ->shouldReceive('retire')
        ->once()
        ->with($stable, $datetime)
        ->andReturns($stable);

    RetireAction::run($stable, $datetime);
});

test('it retires an inactive stable at the current datetime by default', function () {
    $stable = Stable::factory()->inactive()->create();
    $datetime = now();

    $this->stableRepository
        ->shouldNotReceive('deactivate');

    $this->stableRepository
        ->shouldReceive('retire')
        ->once()
        ->withArgs(function (Stable $retirableStable, Carbon $retirementDate) use ($stable, $datetime) {
            expect($retirableStable->is($stable))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($stable);

    RetireAction::run($stable);
});

test('it retires an inactive stable at a specific datetime', function () {
    $stable = Stable::factory()->inactive()->create();
    $datetime = now()->addDays(2);

    $this->stableRepository
        ->shouldNotReceive('deactivate');

    $this->stableRepository
        ->shouldReceive('retire')
        ->once()
        ->with($stable, $datetime)
        ->andReturns($stable);

    RetireAction::run($stable, $datetime);
});

test('it retires the current tag teams and current wrestlers and current managers of a stable', function () {
    $tagTeams = TagTeam::factory()->bookable()->count(1)->create();
    $wrestlers = Wrestler::factory()->bookable()->count(1)->create();
    $managers = Manager::factory()->available()->count(1)->create();
    $datetime = now();

    $stable = Stable::factory()
        ->hasAttached($tagTeams, ['joined_at' => now()])
        ->hasAttached($wrestlers, ['joined_at' => now()])
        ->hasAttached($managers, ['joined_at' => now()])
        ->active()
        ->create();

    $this->stableRepository
        ->shouldReceive('deactivate')
        ->once()
        ->with($stable, $datetime)
        ->andReturns($stable);

    $this->stableRepository
        ->shouldReceive('retire')
        ->once()
        ->with($stable, $datetime)
        ->andReturns($stable);

    TagTeamRetireAction::shouldRun()->times(2);
    WrestlerRetireAction::shouldRun()->times(2);
    ManagerRetireAction::shouldRun()->times(1);

    RetireAction::run($stable, $datetime);
});

test('it throws exception trying to retire a non retirable stable', function ($factoryState) {
    $stable = Stable::factory()->{$factoryState}()->create();

    RetireAction::run($stable);
})->throws(CannotBeRetiredException::class)->with([
    'unactivated',
    'withFutureActivation',
    'retired',
]);
