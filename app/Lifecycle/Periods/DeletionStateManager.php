<?php

declare(strict_types=1);

namespace App\Lifecycle\Periods;

use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Models\Contracts\SoftDeletable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Every write here runs inside the calling Action's transaction and opens none of its own.
 */
final readonly class DeletionStateManager
{
    public function __construct(private LifecycleTransitionRecorder $transitionRecorder) {}

    public function delete(Model&SoftDeletable $subject, Carbon $effectiveAt): void
    {
        $subject->delete();

        $this->transitionRecorder->record(
            $subject,
            LifecycleDimension::Deletion,
            LifecycleTransitionType::Deleted,
            $effectiveAt,
        );
    }

    public function restore(Model&SoftDeletable $subject, Carbon $effectiveAt): void
    {
        $subject->restore();

        $this->transitionRecorder->record(
            $subject,
            LifecycleDimension::Deletion,
            LifecycleTransitionType::Restored,
            $effectiveAt,
        );
    }
}
