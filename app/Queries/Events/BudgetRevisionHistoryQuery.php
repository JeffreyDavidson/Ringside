<?php

declare(strict_types=1);

namespace App\Queries\Events;

use App\Enums\Events\BudgetOperation;
use App\Models\Events\EventBudget;
use App\Models\Events\EventBudgetRevision;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A budget's revisions, newest first, optionally limited to those that touched one line. */
class BudgetRevisionHistoryQuery
{
    /** @return HasMany<EventBudgetRevision, EventBudget> */
    public function get(EventBudget $budget, ?int $lineId): HasMany
    {
        $query = $budget->revisions()->orderByDesc('version');

        if ($lineId === null) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($lineId): void {
            $query->where(fn (Builder $query): Builder => $query->whereIn('operation', [BudgetOperation::SaveLine->value, BudgetOperation::ArchiveLine->value])->where('target_id', $lineId))
                ->orWhere('after->event_budget_line_id', $lineId)->orWhere('before->event_budget_line_id', $lineId);
        });
    }
}
