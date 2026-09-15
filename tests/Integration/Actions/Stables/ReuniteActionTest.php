<?php

declare(strict_types=1);

use App\Actions\Stables\ReuniteAction;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Models\Roster\Stables\Stable;

test('it reunites a disbanded stable and preserves its history', function (): void {
    $stable = Stable::factory()->disbanded()->create();
    $reuniteDate = now()->subDay();

    resolve(ReuniteAction::class)->handle($stable, $reuniteDate);

    $stable->refresh();
    $transition = $stable->lifecycleTransitions()->sole();
    $activityPeriods = $stable->activityPeriods()->orderBy('started_at')->get();
    $currentPeriod = $stable->currentActivityPeriod()->firstOrFail();

    expect($activityPeriods)->toHaveCount(2)
        ->and($currentPeriod->started_at->toDateTimeString())->toBe($reuniteDate->toDateTimeString())
        ->and($currentPeriod->ended_at)->toBeNull()
        ->and($transition->transition)->toBe(LifecycleTransitionType::Reunited)
        ->and($transition->effective_at->toDateTimeString())->toBe($reuniteDate->toDateTimeString());
});
