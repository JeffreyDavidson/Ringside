<?php

declare(strict_types=1);

use App\Actions\Titles\DebutAction;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Titles\CannotBeDebutedException;
use App\Models\Titles\Title;

test('it debuts an unactivated title with its date and notes', function (): void {
    $title = Title::factory()->unactivated()->create();
    $debutedAt = now()->subDay();

    resolve(DebutAction::class)->handle($title, $debutedAt, 'Introduced on television');

    $title->refresh();
    $transition = $title->lifecycleTransitions()->sole();
    $activityPeriod = $title->currentActivityPeriod()->firstOrFail();

    expect($activityPeriod->started_at->toDateTimeString())->toBe($debutedAt->toDateTimeString())
        ->and($transition->transition)->toBe(LifecycleTransitionType::Debuted)
        ->and($transition->effective_at->toDateTimeString())->toBe($debutedAt->toDateTimeString())
        ->and($transition->context)->toBe(['notes' => 'Introduced on television']);
});

test('it rejects debuting a title that already has activity history', function (): void {
    $title = Title::factory()->active()->create();

    expect(fn () => resolve(DebutAction::class)->handle($title))
        ->toThrow(CannotBeDebutedException::class);
});
