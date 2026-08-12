<?php

declare(strict_types=1);

use App\Actions\Lifecycle\RecordLifecycleTransitionAction;
use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Models\Titles\Title;
use App\Models\Users\User;

use function Pest\Laravel\actingAs;

test('it records an attributed lifecycle transition for its subject', function () {
    $user = User::factory()->create();
    $title = Title::factory()->create();
    $effectiveAt = now()->subDay();

    actingAs($user);

    $transition = resolve(RecordLifecycleTransitionAction::class)->handle(
        $title,
        LifecycleDimension::Activity,
        LifecycleTransitionType::Debuted,
        $effectiveAt,
        ['notes' => 'Introduced at the season premiere.'],
    );

    expect($transition->subject->is($title))->toBeTrue()
        ->and($transition->user_id)->toBe($user->id)
        ->and($transition->dimension)->toBe(LifecycleDimension::Activity)
        ->and($transition->transition)->toBe(LifecycleTransitionType::Debuted)
        ->and($transition->effective_at->toDateTimeString())->toBe($effectiveAt->toDateTimeString())
        ->and($transition->context)->toBe(['notes' => 'Introduced at the season premiere.'])
        ->and($title->lifecycleTransitions()->sole()->is($transition))->toBeTrue();
});

test('it records system transitions without an authenticated user', function () {
    $title = Title::factory()->create();

    $transition = resolve(RecordLifecycleTransitionAction::class)->handle(
        $title,
        LifecycleDimension::Activity,
        LifecycleTransitionType::Debuted,
        now(),
    );

    expect($transition->user_id)->toBeNull()
        ->and($transition->context)->toBeNull();
});

test('recorded lifecycle transitions are immutable', function (string $operation) {
    $transition = resolve(RecordLifecycleTransitionAction::class)->handle(
        Title::factory()->create(),
        LifecycleDimension::Activity,
        LifecycleTransitionType::Debuted,
        now(),
    );

    expect(fn () => $operation === 'update'
        ? $transition->update(['context' => ['notes' => 'Changed']])
        : $transition->delete())
        ->toThrow(LogicException::class, 'Lifecycle transition records are immutable.');
})->with(['update', 'delete']);
