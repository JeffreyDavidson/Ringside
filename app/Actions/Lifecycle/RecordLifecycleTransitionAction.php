<?php

declare(strict_types=1);

namespace App\Actions\Lifecycle;

use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleOwnerType;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Models\Lifecycle\LifecycleTransition;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class RecordLifecycleTransitionAction
{
    public function __construct(private Guard $auth) {}

    /** @param array<string, mixed> $context */
    public function handle(
        Model $subject,
        LifecycleDimension $dimension,
        LifecycleTransitionType $transition,
        Carbon $effectiveAt,
        array $context = [],
    ): LifecycleTransition {
        $ownerType = LifecycleOwnerType::fromModel($subject);

        return LifecycleTransition::query()->create([
            'subject_type' => $ownerType->morphAlias(),
            'subject_id' => $subject->getKey(),
            'dimension' => $dimension,
            'transition' => $transition,
            'effective_at' => $effectiveAt,
            'user_id' => $this->auth->id(),
            'context' => $context === [] ? null : $context,
        ]);
    }
}
