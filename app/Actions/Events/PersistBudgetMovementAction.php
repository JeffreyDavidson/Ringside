<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\Events\BudgetMovementData;
use App\Enums\Events\BudgetMovementKind;
use App\Exceptions\Events\BudgetException;
use App\Models\Events\EventBudget;
use App\Models\Events\EventBudgetLine;
use App\Models\Events\EventBudgetMovement;
use App\Models\Users\User;
use App\Services\Events\BudgetSettlementService;
use App\ValueObjects\UsdAmount;
use Illuminate\Support\Carbon;

/** Store a validated movement inside the caller's owning-event transaction. */
class PersistBudgetMovementAction
{
    public function __construct(private readonly BudgetSettlementService $settlement) {}

    public function handle(EventBudget $budget, EventBudgetLine $line, BudgetMovementData $data, ?int $replaces = null): EventBudgetMovement
    {
        $line->setRelation('movements', $line->movements()->orderBy('id')->lockForUpdate()->get());
        if ($line->archived_at !== null) {
            throw BudgetException::archived();
        }
        if ($line->movements->count() >= 1000) {
            throw BudgetException::movementLimit();
        }
        if ($data->amount <= 0 || $data->amount > UsdAmount::MAX_CENTS) {
            throw BudgetException::positiveAmount();
        }
        if (! Carbon::hasFormatWithModifiers($data->date, 'Y-m-d') || ! Carbon::canBeCreatedFromFormat($data->date, 'Y-m-d') || $data->date > now($budget->timezone)->toDateString()) {
            throw BudgetException::paymentDate();
        }
        if ($data->kind === BudgetMovementKind::Refund) {
            $source = $line->movements->firstWhere('id', $data->sourceId);
            if ($source === null || $source->voided_at !== null || $source->kind !== BudgetMovementKind::Payment || $data->date < $source->effective_date->toDateString()) {
                throw BudgetException::refundSource();
            }
            $refunded = $line->movements->filter(fn (EventBudgetMovement $movement): bool => $movement->source_id === $source->id && $movement->voided_at === null)->sum(fn (EventBudgetMovement $movement): int => $movement->amount_cents);
            if ($data->amount > $source->amount_cents - $refunded) {
                throw BudgetException::refundCap();
            }
        }
        if ($data->kind === BudgetMovementKind::Payment && $data->sourceId !== null) {
            throw BudgetException::refundSource();
        }
        if ($data->kind === BudgetMovementKind::Payment && $line->confirmed_cents !== null && $this->settlement->net($line) + $data->amount > $line->confirmed_cents && ! $data->acknowledge) {
            throw BudgetException::excess();
        }
        $user = auth()->user();
        if (! $user instanceof User) {
            throw new \LogicException('A budget movement requires an actor.');
        }
        $movement = new EventBudgetMovement;
        $movement->event_budget_line_id = $line->id;
        $movement->fill([
            'kind' => $data->kind,
            'amount_cents' => $data->amount,
            'effective_date' => Carbon::parse($data->date),
            'method' => $data->method,
            'reference' => $data->reference,
            'notes' => $data->notes,
        ]);
        $movement->source_id = $data->sourceId;
        $movement->replaces_id = $replaces;
        $movement->actor_id = $user->id;
        $movement->actor_label = "{$user->first_name} {$user->last_name}";
        $movement->save();

        return $movement;
    }
}
