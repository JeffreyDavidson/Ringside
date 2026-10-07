<?php

declare(strict_types=1);

use App\Builders\Lifecycle\LifecyclePeriodBuilder;
use App\Models\Lifecycle\ActivityPeriod;
use App\Models\Lifecycle\Employment;
use App\Models\Lifecycle\Injury;
use App\Models\Lifecycle\Retirement;
use App\Models\Lifecycle\Suspension;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @param  Builder<Employment>  $query
 * @return array<int, mixed>
 */
function pluckConstrained(Builder $query, string $constraint, mixed ...$arguments): array
{
    LifecyclePeriodBuilder::$constraint($query, ...$arguments);

    return $query->pluck('id')->all();
}

test('lifecycle period models use the shared builder', function (string $modelClass) {
    /** @var Model $model */
    $model = new $modelClass;

    expect($model->newQuery())->toBeInstanceOf(LifecyclePeriodBuilder::class);
})->with([
    ActivityPeriod::class,
    Employment::class,
    Injury::class,
    Retirement::class,
    Suspension::class,
]);

test('lifecycle periods can be queried by temporal state', function () {
    $this->travelTo(now()->startOfDay());

    $currentEmployment = Employment::factory()
        ->for(Wrestler::factory(), 'employable')
        ->started(now()->subDay())
        ->create();
    $scheduledEmployment = Employment::factory()
        ->for(Wrestler::factory(), 'employable')
        ->started(now()->addDay())
        ->create();
    $endedEmployment = Employment::factory()
        ->for(Wrestler::factory(), 'employable')
        ->started(now()->subMonth())
        ->ended(now()->subDay())
        ->create();

    expect(pluckConstrained(Employment::query(), 'constrainToOpen'))
        ->toEqualCanonicalizing([$currentEmployment->id, $scheduledEmployment->id])
        ->and(pluckConstrained(Employment::query(), 'constrainToEnded'))
        ->toBe([$endedEmployment->id])
        ->and(pluckConstrained(Employment::query(), 'constrainToCurrent'))
        ->toBe([$currentEmployment->id])
        ->and(pluckConstrained(Employment::query(), 'constrainToScheduled'))
        ->toBe([$scheduledEmployment->id]);
});

test('lifecycle periods can be queried as active on a date', function () {
    $date = now()->startOfDay();

    $activeOpenEmployment = Employment::factory()
        ->for(Wrestler::factory(), 'employable')
        ->started($date->copy()->subDay())
        ->create();
    $activeEndedEmployment = Employment::factory()
        ->for(Wrestler::factory(), 'employable')
        ->started($date->copy()->subWeek())
        ->ended($date->copy()->addDay())
        ->create();
    Employment::factory()
        ->for(Wrestler::factory(), 'employable')
        ->started($date->copy()->addDay())
        ->create();
    Employment::factory()
        ->for(Wrestler::factory(), 'employable')
        ->started($date->copy()->subWeek())
        ->ended($date->copy()->subDay())
        ->create();

    expect(pluckConstrained(Employment::query(), 'constrainToActiveOn', $date))
        ->toEqualCanonicalizing([$activeOpenEmployment->id, $activeEndedEmployment->id]);
});
