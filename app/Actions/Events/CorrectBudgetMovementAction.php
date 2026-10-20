<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\Events\BudgetCorrectionData;
use App\Data\Events\BudgetMovementData;
use App\Enums\Events\BudgetOperation;
use App\Exceptions\Events\BudgetException;
use App\Models\Events\Event;
use App\Models\Events\EventBudget;

class CorrectBudgetMovementAction
{
    public function __construct(private readonly MutateBudgetAction $mutate, private readonly PersistBudgetMovementAction $persist) {}

    public function handle(Event $event, int $version, string $key, int $lineId, BudgetCorrectionData $correction): EventBudget
    {
        $movementId = $correction->movementId;
        $reason = $correction->reason;
        $replacement = $correction->replacement;

        return $this->mutate->handle($event, $version, $key, BudgetOperation::CorrectMovement, ['line_id' => $lineId, 'movement_id' => $movementId, 'replacement' => $replacement instanceof BudgetMovementData ? (array) $replacement : null], function (EventBudget $budget) use ($lineId, $movementId, $reason, $replacement): array {
            $line = $budget->lines()->whereKey($lineId)->lockForUpdate()->firstOrFail();
            $movements = $line->movements()->orderBy('id')->lockForUpdate()->get();
            $movement = $movements->find($movementId);
            if ($movement === null) {
                throw BudgetException::movementMissing();
            }
            if ($movement->voided_at !== null) {
                throw BudgetException::alreadyVoided();
            }
            if ($movements->refundsOf($movementId)->unvoided()->isNotEmpty()) {
                throw BudgetException::dependentRefunds();
            }
            $before = $movement->attributesToArray();
            $movement->voided_at = now();
            $movement->voided_by = (int) auth()->id();
            $movement->void_reason = trim($reason);
            $movement->save();
            $replaced = $replacement instanceof BudgetMovementData ? $this->persist->handle($budget, $line, $replacement, $movementId) : null;

            return ['target' => $movement->id, 'before' => $before, 'after' => ['voided' => $movement->attributesToArray(), 'replacement' => $replaced?->attributesToArray()]];
        }, trim($reason));
    }
}
