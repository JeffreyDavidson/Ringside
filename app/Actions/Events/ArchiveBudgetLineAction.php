<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Enums\Events\BudgetOperation;
use App\Exceptions\Events\BudgetException;
use App\Models\Events\Event;
use App\Models\Events\EventBudget;

class ArchiveBudgetLineAction
{
    public function __construct(private readonly MutateBudgetAction $mutate) {}

    public function handle(Event $event, int $version, string $key, int $lineId, string $reason): EventBudget
    {
        return $this->mutate->handle($event, $version, $key, BudgetOperation::ArchiveLine, ['line_id' => $lineId], function (EventBudget $budget) use ($lineId): array {
            $line = $budget->lines()->whereKey($lineId)->lockForUpdate()->firstOrFail();
            if ($line->archived_at !== null || ($line->confirmed_cents !== null && $line->confirmed_cents !== 0) || $line->movements()->exists()) {
                throw BudgetException::cannotArchive();
            }
            $before = $line->attributesToArray();
            $line->archived_at = now();
            $line->save();

            return ['target' => $line->id, 'before' => $before, 'after' => $line->attributesToArray()];
        }, trim($reason));
    }
}
