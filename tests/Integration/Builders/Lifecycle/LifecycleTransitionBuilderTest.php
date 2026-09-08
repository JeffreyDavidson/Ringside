<?php

declare(strict_types=1);

use App\Builders\Lifecycle\LifecycleTransitionBuilder;
use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Models\Lifecycle\LifecycleTransition;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;

test('lifecycle transitions use the shared builder', function () {
    expect(LifecycleTransition::query())->toBeInstanceOf(LifecycleTransitionBuilder::class);
});

test('lifecycle transition relationships are ordered chronologically with deterministic ties', function () {
    // Arrange
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
    LifecycleTransition::factory()
        ->for(Title::factory(), 'subject')
        ->create(['effective_at' => $sharedEffectiveDate]);
    $wrestler = Wrestler::factory()->create(['id' => $title->id]);
    LifecycleTransition::factory()
        ->for($wrestler, 'subject')
        ->create([
            'dimension' => LifecycleDimension::Employment,
            'transition' => LifecycleTransitionType::Employed,
            'effective_at' => $sharedEffectiveDate,
        ]);

    // Act
    $history = $title->lifecycleTransitions();
    $transitionIds = $history->pluck('id');

    // Assert
    expect($transitionIds->all())->toBe([
        $firstTiedTransition->id,
        $secondTiedTransition->id,
        $latestTransition->id,
    ]);
});
