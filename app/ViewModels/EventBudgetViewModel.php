<?php

declare(strict_types=1);

namespace App\ViewModels;

use App\Models\Events\Event;
use App\Models\Events\EventBudget;
use App\Models\Events\EventBudgetLine;
use App\Models\Events\EventBudgetRevision;
use App\Services\Events\BudgetSettlementService;
use App\ValueObjects\UsdAmount;
use Illuminate\Database\Eloquent\Collection;

/** @phpstan-import-type Totals from BudgetSettlementService
 * @phpstan-import-type Row from BudgetSettlementService
 */
class EventBudgetViewModel
{
    public function __construct(private readonly BudgetSettlementService $settlement) {}

    /** @return array{event: Event, budget: ?EventBudget, rows: list<Row>, archived: Collection<int, EventBudgetLine>, totals: Totals} */
    public function data(Event $event): array
    {
        $event = Event::query()->whereKey($event->id)->firstOrFail();
        $event->load('promotion');
        $budget = EventBudget::query()->where('event_id', $event->id)->with('lines.movements')->first();
        $lines = $budget->lines ?? new Collection;
        $rows = [];
        foreach ($lines as $line) {
            if ($line->archived_at === null) {
                $rows[] = $this->settlement->row($line);
            }
        }

        return ['event' => $event, 'budget' => $budget, 'rows' => $rows,
            'archived' => $lines->filter(fn (EventBudgetLine $line): bool => $line->archived_at !== null),
            'totals' => $this->settlement->totals($lines)];
    }

    /**
     * The readable before and after of each revision on the history page, keyed by revision id.
     *
     * @param  iterable<EventBudgetRevision>  $revisions
     * @return array<int, list<array{label: string, before: string, after: string}>>
     */
    public function historyChanges(iterable $revisions): array
    {
        $changes = [];
        foreach ($revisions as $revision) {
            $changes[$revision->id] = $this->changes($revision);
        }

        return $changes;
    }

    /** @return list<array{label: string, before: string, after: string}> */
    private function changes(EventBudgetRevision $revision): array
    {
        $after = is_array($revision->after['voided'] ?? null) ? $revision->after['voided'] : $revision->after;
        $before = $revision->before;
        $changes = [];
        foreach (['description', 'counterparty', 'planned_cents', 'confirmed_cents', 'due_date', 'notes', 'amount_cents', 'effective_date', 'reference', 'void_reason'] as $field) {
            $old = $before[$field] ?? null;
            $new = $after[$field] ?? null;
            if ($old === $new) {
                continue;
            }
            $key = match ($field) {
                'planned_cents' => 'planned', 'confirmed_cents' => 'confirmed', 'amount_cents' => 'amount', 'void_reason' => 'reason', default => $field,
            };
            $changes[] = ['label' => __('event-budget.'.$key), 'before' => $this->historyValue($old, $field), 'after' => $this->historyValue($new, $field)];
        }
        $replacement = $revision->after['replacement'] ?? null;
        if (is_array($replacement) && is_int($replacement['amount_cents'] ?? null)) {
            $changes[] = ['label' => __('event-budget.replace'), 'before' => __('event-budget.unknown'), 'after' => UsdAmount::display($replacement['amount_cents'])];
        }

        return $changes;
    }

    private function historyValue(mixed $value, string $field): string
    {
        if (str_ends_with($field, '_cents')) {
            return UsdAmount::display(is_int($value) ? $value : null);
        }
        if (! is_string($value) || $value === '') {
            return __('event-budget.unknown');
        }
        if (in_array($field, ['due_date', 'effective_date'], true)) {
            return substr($value, 0, 10);
        }

        return $value;
    }
}
