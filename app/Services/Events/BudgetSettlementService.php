<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Collections\EventBudgetLineCollection;
use App\Enums\Events\BudgetDirection;
use App\Enums\Events\BudgetMovementKind;
use App\Models\Events\EventBudgetLine;
use App\Models\Events\EventBudgetMovement;
use Illuminate\Support\Collection as BaseCollection;

/**
 * @phpstan-type DirectionTotals array{planned: int, confirmed: int, cash: int, missing_planned: int, missing_confirmed: int}
 * @phpstan-type Totals array{income: DirectionTotals, expense: DirectionTotals, planned_net: ?int, confirmed_net: ?int, cash_net: int, to_pay: int, to_collect: int, refund_to_send: int, refund_to_collect: int, missing: int, count: int, balanced: bool}
 * @phpstan-type Row array{line: EventBudgetLine, net: int, balance: ?int, variance: ?int, attention: string}
 */
class BudgetSettlementService
{
    /** Movements must be explicitly loaded by the page query or owning locked Action. */
    public function net(EventBudgetLine $line): int
    {
        return $line->movements->unvoided()->sum(fn (EventBudgetMovement $movement): int => $movement->kind === BudgetMovementKind::Refund ? -$movement->amount_cents : $movement->amount_cents);
    }

    /** @return Row */
    public function row(EventBudgetLine $line): array
    {
        $net = $this->net($line);
        $balance = $line->confirmed_cents === null ? null : $line->confirmed_cents - $net;
        $attention = 'balanced';

        if ($balance === null) {
            $attention = 'needs_amount';
        }

        if ($balance !== null && $balance > 0) {
            $attention = $line->direction === BudgetDirection::Expense ? 'to_pay' : 'to_collect';
        }

        if ($balance !== null && $balance < 0) {
            $attention = $line->direction === BudgetDirection::Expense ? 'refund_to_collect' : 'refund_to_send';
        }

        return ['line' => $line, 'net' => $net, 'balance' => $balance,
            'variance' => $line->planned_cents === null || $line->confirmed_cents === null ? null : $line->confirmed_cents - $line->planned_cents, 'attention' => $attention];
    }

    /** @return DirectionTotals */
    private function directionTotals(EventBudgetLineCollection $lines, BudgetDirection $direction): array
    {
        $directed = $lines->unarchived()->inDirection($direction);

        return [
            'planned' => $directed->sum(fn (EventBudgetLine $line): int => $line->planned_cents ?? 0),
            'confirmed' => $directed->sum(fn (EventBudgetLine $line): int => $line->confirmed_cents ?? 0),
            'cash' => $directed->sum(fn (EventBudgetLine $line): int => $this->net($line)),
            'missing_planned' => $directed->whereNull('planned_cents')->count(),
            'missing_confirmed' => $directed->whereNull('confirmed_cents')->count(),
        ];
    }

    /** @return Totals */
    public function totals(EventBudgetLineCollection $lines): array
    {
        $income = $this->directionTotals($lines, BudgetDirection::Income);
        $expense = $this->directionTotals($lines, BudgetDirection::Expense);
        $rows = $lines->unarchived()->map(fn (EventBudgetLine $line): array => $this->row($line));
        $count = $rows->count();
        $balances = [
            'to_pay' => $this->balanceOf($rows, 'to_pay'),
            'to_collect' => $this->balanceOf($rows, 'to_collect'),
            'refund_to_send' => -$this->balanceOf($rows, 'refund_to_send'),
            'refund_to_collect' => -$this->balanceOf($rows, 'refund_to_collect'),
        ];
        $missing = $income['missing_confirmed'] + $expense['missing_confirmed'];

        return ['income' => $income, 'expense' => $expense,
            'planned_net' => $count > 0 && $income['missing_planned'] + $expense['missing_planned'] === 0 ? $income['planned'] - $expense['planned'] : null,
            'confirmed_net' => $count > 0 && $missing === 0 ? $income['confirmed'] - $expense['confirmed'] : null,
            'cash_net' => $income['cash'] - $expense['cash'], ...$balances,
            'missing' => $missing, 'count' => $count,
            'balanced' => $count > 0 && $missing === 0 && array_sum($balances) === 0];
    }

    /**
     * The summed signed balance of the rows labelled with the given attention.
     *
     * @param  BaseCollection<int, Row>  $rows
     */
    private function balanceOf(BaseCollection $rows, string $attention): int
    {
        return $rows->where('attention', $attention)->sum(fn (array $row): int => $row['balance'] ?? 0);
    }
}
