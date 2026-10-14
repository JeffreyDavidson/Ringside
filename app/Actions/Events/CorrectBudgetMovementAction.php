<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\Events\BudgetMovementData;
use App\Enums\Events\BudgetOperation;
use App\Exceptions\Events\BudgetException;
use App\Models\Events\Event;
use App\Models\Events\EventBudget;
use Illuminate\Support\Facades\Validator;

class CorrectBudgetMovementAction
{
    public function __construct(private readonly MutateBudgetAction $mutate, private readonly RecordBudgetMovementAction $record, private readonly PersistBudgetMovementAction $persist) {}

    /** @param ?array<string, mixed> $replacement */
    public function handle(Event $event, int $version, string $key, int $lineId, int $movementId, string $reason, ?array $replacement = null): EventBudget
    {
        Validator::make(['reason' => $reason], ['reason' => ['required', 'string', 'max:1000']])->validate();
        $data = $replacement === null ? null : $this->record->data($replacement);

        return $this->mutate->handle($event, $version, $key, BudgetOperation::CorrectMovement, ['line_id' => $lineId, 'movement_id' => $movementId, 'replacement' => $data instanceof BudgetMovementData ? (array) $data : null], function (EventBudget $budget) use ($lineId, $movementId, $reason, $data): array {
            $line = $budget->lines()->whereKey($lineId)->lockForUpdate()->firstOrFail();
            $movements = $line->movements()->orderBy('id')->lockForUpdate()->get();
            $movement = $movements->firstWhere('id', $movementId);
            if ($movement === null) {
                abort(404);
            }
            if ($movement->voided_at !== null) {
                throw BudgetException::because('already_voided');
            }
            if ($movements->where('source_id', $movementId)->whereNull('voided_at')->isNotEmpty()) {
                throw BudgetException::because('dependent_refunds');
            }
            $before = $movement->attributesToArray();
            $movement->voided_at = now();
            $movement->voided_by = (int) auth()->id();
            $movement->void_reason = trim($reason);
            $movement->save();
            $replacement = $data instanceof BudgetMovementData ? $this->persist->handle($budget, $line, $data, $movementId) : null;

            return ['target' => $movement->id, 'before' => $before, 'after' => ['voided' => $movement->attributesToArray(), 'replacement' => $replacement?->attributesToArray()]];
        }, trim($reason));
    }
}
