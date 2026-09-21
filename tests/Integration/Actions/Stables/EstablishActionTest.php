<?php

declare(strict_types=1);

use App\Actions\Stables\EstablishAction;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Models\Roster\Stables\Stable;

test('it establishes a stable with the supplied activity period dates', function (): void {
    $stable = Stable::factory()->withEmployedDefaultMembers()->create();
    $activationDate = now()->addDay();
    $endDate = now()->addMonth();

    $activityPeriod = resolve(EstablishAction::class)->handle($stable, $activationDate, $endDate);

    $stable->refresh();
    $transition = $stable->lifecycleTransitions()->sole();

    expect($activityPeriod->started_at->toDateTimeString())->toBe($activationDate->toDateTimeString())
        ->and(requiredDate($activityPeriod->ended_at)->toDateTimeString())->toBe($endDate->toDateTimeString())
        ->and($stable->currentActivityPeriod()->exists())->toBeFalse()
        ->and($stable->activityPeriods()->count())->toBe(1)
        ->and($transition->transition)->toBe(LifecycleTransitionType::Established)
        ->and($transition->effective_at->toDateTimeString())->toBe($activationDate->toDateTimeString());
});
