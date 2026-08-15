<?php

declare(strict_types=1);

use App\Builders\Lifecycle\LifecycleTransitionBuilder;
use App\Models\Lifecycle\LifecycleTransition;
use App\Models\Titles\Title;

test('lifecycle transitions use the shared builder', function () {
    expect(LifecycleTransition::query())->toBeInstanceOf(LifecycleTransitionBuilder::class);
});

test('lifecycle transitions can be ordered chronologically with deterministic ties', function () {
    $title = Title::factory()->create();
    $sharedEffectiveDate = now()->subDay();

    $latestTransition = LifecycleTransition::factory()
        ->for($title, 'subject')
        ->create(['effective_at' => now()]);
    $firstTiedTransition = LifecycleTransition::factory()
        ->for($title, 'subject')
        ->create(['effective_at' => $sharedEffectiveDate]);
    $secondTiedTransition = LifecycleTransition::factory()
        ->for($title, 'subject')
        ->create(['effective_at' => $sharedEffectiveDate]);

    expect(LifecycleTransition::query()->chronological()->pluck('id')->all())->toBe([
        $firstTiedTransition->id,
        $secondTiedTransition->id,
        $latestTransition->id,
    ])->and($title->lifecycleTransitions()->pluck('id')->all())->toBe([
        $firstTiedTransition->id,
        $secondTiedTransition->id,
        $latestTransition->id,
    ]);
});
