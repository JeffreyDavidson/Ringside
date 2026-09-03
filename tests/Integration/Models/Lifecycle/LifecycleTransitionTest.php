<?php

declare(strict_types=1);

use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Models\Lifecycle\LifecycleTransition;
use Illuminate\Database\Eloquent\Factories\HasFactory;

test('it defines the lifecycle transition persistence boundary', function () {
    $transition = new LifecycleTransition();

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
