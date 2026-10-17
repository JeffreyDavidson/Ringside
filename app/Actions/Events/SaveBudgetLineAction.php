<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\Events\BudgetLineData;
use App\Enums\Events\BudgetOperation;
use App\Exceptions\Events\BudgetException;
use App\Models\Events\Event;
use App\Models\Events\EventBudget;
use App\Models\Events\EventBudgetLine;
use App\Services\Events\BudgetSettlementService;

class SaveBudgetLineAction
{
    public function __construct(private readonly MutateBudgetAction $mutate, private readonly BudgetSettlementService $settlement) {}

    public function handle(Event $event, int $version, string $key, ?int $lineId, BudgetLineData $data): EventBudget
    {
        if (! $data->category->accepts($data->direction)) {
            throw BudgetException::categoryDirection();
        }
        $attributes = ['direction' => $data->direction, 'category' => $data->category,
            'description' => $data->description, 'counterparty' => $data->counterparty,
            'planned_cents' => $data->plannedCents, 'confirmed_cents' => $data->confirmedCents,
            'due_date' => $data->dueDate, 'notes' => $data->notes];
        $reason = $data->reason;

        return $this->mutate->handle($event, $version, $key, BudgetOperation::SaveLine, ['line_id' => $lineId, ...$attributes, 'acknowledge' => $data->acknowledge], function (EventBudget $budget) use ($lineId, $attributes, $reason, $data): ?array {
            $line = $lineId === null ? new EventBudgetLine : $budget->lines()->whereKey($lineId)->lockForUpdate()->firstOrFail();
            if ($line->archived_at !== null) {
                throw BudgetException::archived();
            }
            if ($lineId === null && $budget->lines()->count() >= 500) {
                throw BudgetException::lineLimit();
            }
            $before = $line->exists ? $line->attributesToArray() : [];
            $line->setRelation('movements', $line->movements()->orderBy('id')->lockForUpdate()->get());
            if ($line->exists && ($line->direction !== $attributes['direction']) && $line->movements->isNotEmpty()) {
                throw BudgetException::directionLocked();
            }
            $confirmedChanged = $line->exists && $line->confirmed_cents !== $attributes['confirmed_cents'];
            if ($confirmedChanged && $reason === null) {
                throw BudgetException::reasonRequired();
            }
            if (($confirmedChanged || ! $line->exists) && $attributes['confirmed_cents'] !== null && $this->settlement->net($line) > $attributes['confirmed_cents'] && ! $data->acknowledge) {
                throw BudgetException::excess();
            }
            $line->forceFill($attributes);
            if ($line->exists && ! $line->isDirty()) {
                return null;
            }
            $line->event_budget_id = $budget->id;
            $line->save();

            return ['target' => $line->id, 'before' => $before, 'after' => $line->attributesToArray()];
        }, $reason);
    }
}
