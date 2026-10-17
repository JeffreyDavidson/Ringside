<?php

declare(strict_types=1);

use App\Enums\Events\BudgetMovementKind;
use App\Enums\Events\BudgetPaymentMethod;
use App\Models\Events\EventBudgetMovement;
use Illuminate\Support\Carbon;

test('mass assignment accepts the payment details and ignores ownership, actor and void state', function (): void {
    // Arrange
    $movement = new EventBudgetMovement;

    // Act
    $movement->fill([
        'kind' => BudgetMovementKind::Payment,
        'amount_cents' => 2500,
        'effective_date' => '2026-10-01',
        'method' => BudgetPaymentMethod::Cash,
        'reference' => 'R-1',
        'notes' => 'Deposit',
        'event_budget_line_id' => 99,
        'source_id' => 98,
        'replaces_id' => 97,
        'actor_id' => 96,
        'actor_label' => 'Someone Else',
        'voided_at' => Carbon::parse('2026-01-01'),
        'voided_by' => 95,
        'void_reason' => 'Forged',
    ]);

    // Assert
    expect(array_keys($movement->getAttributes()))->toEqualCanonicalizing([
        'kind', 'amount_cents', 'effective_date', 'method', 'reference', 'notes',
    ]);
});
