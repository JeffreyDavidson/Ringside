<?php

declare(strict_types=1);

namespace App\Lifecycle;

use App\Actions\Lifecycle\RecordLifecycleTransitionAction;
use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Models\Contracts\SoftDeletable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class DeletionStateManager
{
    public function __construct(private RecordLifecycleTransitionAction $recordLifecycleTransition) {}

    public function delete(Model&SoftDeletable $subject, Carbon $effectiveAt): void
    {
        DB::transaction(function () use ($subject, $effectiveAt): void {
            $subject->delete();

            $this->recordLifecycleTransition->handle(
                $subject,
                LifecycleDimension::Deletion,
                LifecycleTransitionType::Deleted,
                $effectiveAt,
            );
        });
    }

    public function restore(Model&SoftDeletable $subject, Carbon $effectiveAt): void
    {
        DB::transaction(function () use ($subject, $effectiveAt): void {
            $subject->restore();

            $this->recordLifecycleTransition->handle(
                $subject,
                LifecycleDimension::Deletion,
                LifecycleTransitionType::Restored,
                $effectiveAt,
            );
        });
    }
}
