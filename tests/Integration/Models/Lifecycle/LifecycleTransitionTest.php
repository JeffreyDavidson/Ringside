<?php

declare(strict_types=1);

use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Models\Lifecycle\LifecycleTransition;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

test('it defines the lifecycle transition persistence boundary', function () {
    $transition = new LifecycleTransition;

    expect($transition->getTable())->toBe('lifecycle_transitions')
        ->and($transition->getFillable())->toBe([
            'subject_type',
            'subject_id',
            'dimension',
            'transition',
            'effective_at',
            'user_id',
            'context',
        ])
        ->and($transition->getCasts()['dimension'])->toBe(LifecycleDimension::class)
        ->and($transition->getCasts()['transition'])->toBe(LifecycleTransitionType::class)
        ->and($transition->getCasts()['effective_at'])->toBe('datetime')
        ->and($transition->getCasts()['context'])->toBe('array')
        ->and(class_uses(LifecycleTransition::class))->toContain(HasFactory::class);
});

it('resolves the user who performed a transition', function () {
    $user = User::factory()->create();
    $transition = LifecycleTransition::factory()->create(['user_id' => $user->id]);

    $performedBy = $transition->user;

    expect($performedBy)->toBeInstanceOf(User::class)
        ->and($performedBy?->is($user))->toBeTrue();
});

it('has no user for a transition without an actor', function () {
    $transition = LifecycleTransition::factory()->create(['user_id' => null]);

    $performedBy = $transition->user;

    expect($performedBy)->toBeNull();
});
