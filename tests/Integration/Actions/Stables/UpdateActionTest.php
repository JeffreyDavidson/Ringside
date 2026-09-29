<?php

declare(strict_types=1);

use App\Actions\Stables\UpdateAction;
use App\Data\Stables\StableData;
use App\Data\Stables\StableMembershipData;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Lifecycle\InvalidDateRangeException;
use App\Models\Roster\Stables\Stable;

test('it rejects an activity end date before the start date', function () {
    $stable = Stable::factory()->inactive()->create(['name' => 'Original Name']);
    $originalPeriod = $stable->firstActivityPeriod()->firstOrFail();
    $startedAt = now()->subMonth();

    $data = new StableData(
        name: 'Updated Name',
        start_date: $startedAt,
        members: new StableMembershipData,
        end_date: $startedAt->copy()->subSecond(),
    );

    expect(fn () => resolve(UpdateAction::class)->handle($stable, $data))
        ->toThrow(InvalidDateRangeException::class)
        ->and($stable->refresh()->name)->toBe('Original Name')
        ->and($originalPeriod->refresh()->started_at->toDateTimeString())->toBe($originalPeriod->started_at->toDateTimeString());
});

test('it establishes a stable that has no activity period when a start date is given', function (?int $daysUntilEnd) {
    $stable = Stable::factory()->withEmployedDefaultMembers()->create(['name' => 'Original Name']);
    $startedAt = now()->subMonth()->startOfSecond();
    $endedAt = $daysUntilEnd === null ? null : $startedAt->copy()->addDays($daysUntilEnd);

    $updatedStable = resolve(UpdateAction::class)->handle($stable, new StableData(
        name: '  Renamed Stable  ',
        start_date: $startedAt,
        members: new StableMembershipData,
        end_date: $endedAt,
    ));

    $activityPeriod = $updatedStable->activityPeriods()->sole();

    expect($updatedStable->name)->toBe('Renamed Stable')
        ->and($activityPeriod->started_at->toDateTimeString())->toBe($startedAt->toDateTimeString())
        ->and($activityPeriod->ended_at?->toDateTimeString())->toBe($endedAt?->toDateTimeString())
        ->and($updatedStable->lifecycleTransitions()
            ->where('transition', LifecycleTransitionType::Established)
            ->exists())->toBeTrue();
})->with([
    'open-ended activity' => [null],
    'activity with an end date' => [10],
]);

test('it does not create an activity period when no start date is given', function () {
    $stable = Stable::factory()->unactivated()->create(['name' => 'Original Name']);

    $updatedStable = resolve(UpdateAction::class)->handle($stable, new StableData(
        name: 'Renamed Stable',
        start_date: null,
        members: new StableMembershipData,
    ));

    expect($updatedStable->name)->toBe('Renamed Stable')
        ->and($updatedStable->activityPeriods()->exists())->toBeFalse();
});
