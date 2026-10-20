<?php

declare(strict_types=1);

use App\Models\Events\EventBudget;
use App\Models\Events\EventBudgetLine;
use App\Models\Events\EventBudgetMovement;
use App\Services\Events\BudgetSettlementService;

/**
 * A budget with two expense and three income lines in play, one archived line per direction and a voided payment.
 *
 * @return array{budget: EventBudget, expense: EventBudgetLine}
 */
function mixedSettlementBudget(): array
{
    $budget = EventBudget::factory()->create();
    $line = fn (string $direction, ?int $planned, ?int $confirmed, ?string $archivedAt = null): EventBudgetLine => EventBudgetLine::factory()
        ->for($budget, 'budget')
        ->create([
            'direction' => $direction,
            'category' => $direction === 'income' ? 'tickets' : 'talent',
            'planned_cents' => $planned,
            'confirmed_cents' => $confirmed,
            'archived_at' => $archivedAt,
        ]);

    $expense = $line('expense', 50000, 50000);
    $payment = EventBudgetMovement::factory()->for($expense, 'line')->create(['amount_cents' => 20000]);
    EventBudgetMovement::factory()->for($expense, 'line')->create(['amount_cents' => 5000, 'voided_at' => now()]);
    EventBudgetMovement::factory()->for($expense, 'line')->create(['kind' => 'refund', 'amount_cents' => 3000, 'source_id' => $payment->id]);

    $overpaid = $line('expense', null, 10000);
    EventBudgetMovement::factory()->for($overpaid, 'line')->create(['amount_cents' => 20000]);

    $archivedExpense = $line('expense', 99999, 99999, '2026-01-01 00:00:00');
    EventBudgetMovement::factory()->for($archivedExpense, 'line')->create(['amount_cents' => 1]);

    $noConfirmedAmount = $line('income', 100000, null);
    $refundDue = $line('income', 40000, 40000);
    EventBudgetMovement::factory()->for($refundDue, 'line')->create(['amount_cents' => 60000]);
    $collectDue = $line('income', 10000, 15000);
    EventBudgetMovement::factory()->for($collectDue, 'line')->create(['amount_cents' => 10000]);

    $archivedIncome = $line('income', 77777, 77777, '2026-01-01 00:00:00');
    EventBudgetMovement::factory()->for($archivedIncome, 'line')->create(['amount_cents' => 2]);

    return ['budget' => $budget, 'expense' => $expense];
}

test('the net of a line adds payments, subtracts refunds and ignores voided movements', function (): void {
    // Arrange
    $fixture = mixedSettlementBudget();
    $line = EventBudgetLine::query()->with('movements')->findOrFail($fixture['expense']->id);

    // Act
    $net = resolve(BudgetSettlementService::class)->net($line);

    // Assert
    expect($net)->toBe(17000);
});

test('totals count only unarchived lines, sum each direction and total the balances', function (): void {
    // Arrange
    $fixture = mixedSettlementBudget();
    $lines = EventBudget::query()->with('lines.movements')->findOrFail($fixture['budget']->id)->lines;

    // Act
    $totals = resolve(BudgetSettlementService::class)->totals($lines);

    // Assert
    expect($totals)->toBe([
        'income' => ['planned' => 150000, 'confirmed' => 55000, 'cash' => 70000, 'missing_planned' => 0, 'missing_confirmed' => 1],
        'expense' => ['planned' => 50000, 'confirmed' => 60000, 'cash' => 37000, 'missing_planned' => 1, 'missing_confirmed' => 0],
        'planned_net' => null,
        'confirmed_net' => null,
        'cash_net' => 33000,
        'to_pay' => 33000,
        'to_collect' => 5000,
        'refund_to_send' => 20000,
        'refund_to_collect' => 10000,
        'missing' => 1,
        'count' => 5,
        'balanced' => false,
    ]);
});

test('totals of a budget with every amount confirmed and settled are balanced with net figures', function (): void {
    // Arrange
    $budget = EventBudget::factory()->create();
    $income = EventBudgetLine::factory()->for($budget, 'budget')->create(['direction' => 'income', 'category' => 'tickets', 'planned_cents' => 30000, 'confirmed_cents' => 25000]);
    EventBudgetMovement::factory()->for($income, 'line')->create(['amount_cents' => 25000]);
    $expense = EventBudgetLine::factory()->for($budget, 'budget')->create(['direction' => 'expense', 'planned_cents' => 10000, 'confirmed_cents' => 12000]);
    EventBudgetMovement::factory()->for($expense, 'line')->create(['amount_cents' => 12000]);
    $lines = EventBudget::query()->with('lines.movements')->findOrFail($budget->id)->lines;

    // Act
    $totals = resolve(BudgetSettlementService::class)->totals($lines);

    // Assert
    expect($totals['planned_net'])->toBe(20000)
        ->and($totals['confirmed_net'])->toBe(13000)
        ->and($totals['cash_net'])->toBe(13000)
        ->and($totals['missing'])->toBe(0)
        ->and($totals['balanced'])->toBeTrue();
});

test('totals of a budget with no lines are empty and never balanced', function (): void {
    // Arrange
    $budget = EventBudget::factory()->create();
    $lines = EventBudget::query()->with('lines.movements')->findOrFail($budget->id)->lines;

    // Act
    $totals = resolve(BudgetSettlementService::class)->totals($lines);

    // Assert
    expect($totals['count'])->toBe(0)
        ->and($totals['planned_net'])->toBeNull()
        ->and($totals['confirmed_net'])->toBeNull()
        ->and($totals['balanced'])->toBeFalse();
});
