<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Enums\Events\BudgetDirection;
use App\Enums\Events\BudgetMovementKind;
use App\Models\Events\EventBudgetLine;
use Illuminate\Database\Eloquent\Collection;

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
        $net = 0;
        foreach ($line->movements as $movement) {
            if ($movement->voided_at !== null) {
                continue;
            }
            $net += $movement->kind === BudgetMovementKind::Refund ? -$movement->amount_cents : $movement->amount_cents;
        }

        return $net;
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

    /** @param Collection<int, EventBudgetLine> $lines
     * @return DirectionTotals
     */
    private function directionTotals(Collection $lines, BudgetDirection $direction): array
    {
        $totals = ['planned' => 0, 'confirmed' => 0, 'cash' => 0, 'missing_planned' => 0, 'missing_confirmed' => 0];
        foreach ($lines as $line) {
            if ($line->archived_at !== null || $line->direction !== $direction) {
                continue;
            }
            $totals['planned'] += $line->planned_cents ?? 0;
            $totals['confirmed'] += $line->confirmed_cents ?? 0;
            $totals['cash'] += $this->net($line);
            $totals['missing_planned'] += $line->planned_cents === null ? 1 : 0;
            $totals['missing_confirmed'] += $line->confirmed_cents === null ? 1 : 0;
        }

        return $totals;
    }

    /** @param Collection<int, EventBudgetLine> $lines
     * @return Totals
     */
    public function totals(Collection $lines): array
    {
        $income = $this->directionTotals($lines, BudgetDirection::Income);
        $expense = $this->directionTotals($lines, BudgetDirection::Expense);
        $balances = ['to_pay' => 0, 'to_collect' => 0, 'refund_to_send' => 0, 'refund_to_collect' => 0];
        $count = 0;
        foreach ($lines as $line) {
            if ($line->archived_at !== null) {
                continue;
            }
            $count++;
            $row = $this->row($line);
            $balance = $row['balance'];
            if ($balance === null || $balance === 0) {
                continue;
            }
            if ($line->direction === BudgetDirection::Expense && $balance > 0) {
                $balances['to_pay'] += $balance;
            }
            if ($line->direction === BudgetDirection::Income && $balance > 0) {
                $balances['to_collect'] += $balance;
            }
            if ($line->direction === BudgetDirection::Expense && $balance < 0) {
                $balances['refund_to_collect'] -= $balance;
            }
            if ($line->direction === BudgetDirection::Income && $balance < 0) {
                $balances['refund_to_send'] -= $balance;
            }
        }
        $missing = $income['missing_confirmed'] + $expense['missing_confirmed'];

        return ['income' => $income, 'expense' => $expense,
            'planned_net' => $count > 0 && $income['missing_planned'] + $expense['missing_planned'] === 0 ? $income['planned'] - $expense['planned'] : null,
            'confirmed_net' => $count > 0 && $missing === 0 ? $income['confirmed'] - $expense['confirmed'] : null,
            'cash_net' => $income['cash'] - $expense['cash'], ...$balances,
            'missing' => $missing, 'count' => $count,
            'balanced' => $count > 0 && $missing === 0 && array_sum($balances) === 0];
    }
}
