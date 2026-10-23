<?php

declare(strict_types=1);

namespace App\Lifecycle\Periods;

use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleOwnerType;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Models\Lifecycle\LifecycleTransition;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Writes the immutable lifecycle audit trail.
 *
 * The one known exception to "writers take the actor as a parameter": it reads the signed-in user from the auth guard,
 * so a transition recorded by a job or command has no user. Passing the actor through every caller is a separate plan.
 */
final readonly class LifecycleTransitionRecorder
{
    public function __construct(private Guard $auth) {}

    /** @param array<string, mixed> $context */
    public function record(
        Model $subject,
        LifecycleDimension $dimension,
        LifecycleTransitionType $transition,
        Carbon $effectiveAt,
        array $context = [],
    ): LifecycleTransition {
        LifecycleOwnerType::fromModel($subject);

        return LifecycleTransition::query()->create([
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'dimension' => $dimension,
            'transition' => $transition,
            'effective_at' => $effectiveAt,
            'user_id' => $this->auth->id(),
            'context' => $context === [] ? null : $context,
        ]);
    }
}
