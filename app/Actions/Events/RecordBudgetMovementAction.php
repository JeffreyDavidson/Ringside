<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\Events\BudgetMovementData;
use App\Enums\Events\BudgetOperation;
use App\Models\Events\Event;
use App\Models\Events\EventBudget;
use App\Models\Users\User;

class RecordBudgetMovementAction
{
    public function __construct(private readonly MutateBudgetAction $mutate, private readonly PersistBudgetMovementAction $persist) {}

    public function handle(Event $event, int $version, string $key, int $lineId, BudgetMovementData $data, User $actor): EventBudget
    {
        return $this->mutate->handle($event, $version, $key, BudgetOperation::RecordMovement, ['line_id' => $lineId, ...(array) $data], function (EventBudget $budget) use ($lineId, $data, $actor): array {
            $line = $budget->lines()->whereKey($lineId)->lockForUpdate()->firstOrFail();
            $movement = $this->persist->handle($budget, $line, $data, $actor);

            return ['target' => $movement->id, 'before' => [], 'after' => $movement->attributesToArray()];
        }, $actor);
    }
}
