<?php

declare(strict_types=1);

use App\Actions\Stables\RestoreAction;
use App\Enums\Stables\StableStatus;
use App\Exceptions\Roster\Stables\CannotBeRestoredException;
use App\Lifecycle\Roster\Stables\StableDeletionEligibility;
use App\Lifecycle\Roster\Stables\StableNameLock;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Stables\Stable;
use Illuminate\Support\Facades\DB;

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

test('it reports a deleted stable without conflicts as restorable', function () {
    $stable = Stable::factory()->create();
    $stable->delete();

    expect(resolve(StableDeletionEligibility::class)->canRestore($stable))->toBeTrue();
});

test('it locks the name of a stable without a promotion before the stable row', function () {
    // Arrange
    $stable = Stable::factory()->create(['name' => 'The Alliance']);
    $stable->delete();
    $nameKey = resolve(StableNameLock::class)->key('The Alliance');

    // Act
    $statements = recordStatements(fn () => resolve(RestoreAction::class)->handle($stable));

    // Assert
    $lockPosition = statementPosition($statements, fn (array $statement): bool => str_starts_with($statement['sql'], 'insert into "stable_name_locks"'));
    $stableLockPosition = statementPosition($statements, fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "stables"'));

    expect($statements[$lockPosition]['bindings'])->toBe([$nameKey])
        ->and($lockPosition)->toBeLessThan($stableLockPosition);
});

test('it takes no name lock for a stable of a promotion', function () {
    // Arrange
    $stable = Stable::factory()->for(Promotion::factory(), 'promotion')->create();
    $stable->delete();

    // Act
    resolve(RestoreAction::class)->handle($stable);

    // Assert
    expect(DB::table('stable_name_locks')->exists())->toBeFalse();
});
