<?php

declare(strict_types=1);

use App\Actions\Stables\DisbandAction;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Models\Roster\Stables\Stable;

test('it disbands a stable, ends its activity, and removes current members', function (): void {
    $stable = Stable::factory()->active()->create();
    $disbandedAt = now()->startOfSecond();

    expect($stable->currentWrestlers()->count())->toBeGreaterThan(0)
        ->and($stable->currentTagTeams()->count())->toBeGreaterThan(0);

    resolve(DisbandAction::class)->handle($stable, $disbandedAt);

    $stable->refresh();
    $activityPeriod = $stable->activityPeriods()->sole();
    $transition = $stable->lifecycleTransitions()->sole();

    expect($stable->currentActivityPeriod()->exists())->toBeFalse()
        ->and($stable->currentWrestlers()->exists())->toBeFalse()
        ->and($stable->currentTagTeams()->exists())->toBeFalse()
        ->and(requiredDate($activityPeriod->ended_at)->toDateTimeString())->toBe($disbandedAt->toDateTimeString())
        ->and($transition->transition)->toBe(LifecycleTransitionType::Disbanded)
        ->and($transition->effective_at->toDateTimeString())->toBe($disbandedAt->toDateTimeString());
});
