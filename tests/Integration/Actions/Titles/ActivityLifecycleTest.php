<?php

declare(strict_types=1);

use App\Actions\Titles\DebutAction;
use App\Actions\Titles\PullAction;
use App\Actions\Titles\ReinstateAction;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Titles\CannotBePulledException;
use App\Models\Titles\Title;

test('title activity actions preserve an attributed transition history', function () {
    $title = Title::factory()->unactivated()->create();
    $debutedAt = now()->subMonths(3);
    $pulledAt = now()->subMonths(2);
    $reinstatedAt = now()->subMonth();

    resolve(DebutAction::class)->handle($title, $debutedAt, 'Original debut');
    resolve(PullAction::class)->handle($title, $pulledAt, 'Temporarily withdrawn');
    resolve(ReinstateAction::class)->handle($title, $reinstatedAt, 'Returned to competition');

    $transitions = $title->lifecycleTransitions()->get();

    expect($transitions->pluck('transition')->all())->toBe([
        LifecycleTransitionType::Debuted,
        LifecycleTransitionType::Pulled,
        LifecycleTransitionType::Reinstated,
    ])->and($transitions->pluck('context')->all())->toBe([
        ['notes' => 'Original debut'],
        ['notes' => 'Temporarily withdrawn'],
        ['notes' => 'Returned to competition'],
    ])->and($transitions->pluck('effective_at')->map->toDateTimeString()->all())->toBe([
        $debutedAt->toDateTimeString(),
        $pulledAt->toDateTimeString(),
        $reinstatedAt->toDateTimeString(),
    ]);
});

test('a failed title activity transition does not write an audit record', function () {
    $title = Title::factory()->unactivated()->create();

    expect(fn () => resolve(PullAction::class)->handle($title))->toThrow(CannotBePulledException::class);
    expect($title->lifecycleTransitions()->doesntExist())->toBeTrue();
});
