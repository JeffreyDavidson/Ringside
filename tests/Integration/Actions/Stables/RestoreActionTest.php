<?php

declare(strict_types=1);

use App\Actions\Stables\RestoreAction;
use App\Enums\Stables\StableStatus;
use App\Exceptions\Roster\Stables\CannotBeRestoredException;
use App\Lifecycle\StableDeletionEligibility;
use App\Models\Roster\Stables\Stable;

test('it restores a stable without former members', function () {
    $stable = Stable::factory()->create();
    $stable->delete();

    resolve(RestoreAction::class)->handle($stable);

    expect($stable->refresh()->trashed())->toBeFalse()
        ->and($stable->status)->toBe(StableStatus::Unformed)
        ->and($stable->activityPeriods)->toBeEmpty();
});

test('it preserves historical activity without reuniting the stable', function () {
    $stable = Stable::factory()->inactive()->create();
    $activityPeriodCount = $stable->activityPeriods()->count();
    $stable->delete();

    resolve(RestoreAction::class)->handle($stable);

    expect($stable->refresh()->status)->toBe(StableStatus::Inactive)
        ->and($stable->activityPeriods)->toHaveCount($activityPeriodCount)
        ->and($stable->currentActivityPeriod)->toBeNull();
});

test('it rejects a stable that is not deleted', function () {
    $stable = Stable::factory()->create();

    expect(resolve(StableDeletionEligibility::class)->canRestore($stable))->toBeFalse()
        ->and(fn () => resolve(RestoreAction::class)->handle($stable))
        ->toThrow(CannotBeRestoredException::class);
});

test('it rejects an active stable with the same name', function () {
    $stable = Stable::factory()->create();
    $stable->delete();
    Stable::factory()->active()->create(['name' => $stable->name]);

    expect(resolve(StableDeletionEligibility::class)->canRestore($stable))->toBeFalse()
        ->and(fn () => resolve(RestoreAction::class)->handle($stable))
        ->toThrow(CannotBeRestoredException::class);
});

test('it restores a stable after the conflicting stable is deleted', function () {
    $stable = Stable::factory()->create();
    $stable->delete();
    $conflictingStable = Stable::factory()->create(['name' => $stable->name]);
    $conflictingStable->delete();

    resolve(RestoreAction::class)->handle($stable);

    expect($stable->refresh()->trashed())->toBeFalse();
});
