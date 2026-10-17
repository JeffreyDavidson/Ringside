<?php

declare(strict_types=1);

use App\Enums\Events\BudgetCategory;
use App\Enums\Events\BudgetDirection;
use App\Models\Events\EventBudgetLine;
use Illuminate\Support\Carbon;

test('mass assignment accepts the line details and ignores ownership and archive state', function (): void {
    // Arrange
    $line = new EventBudgetLine;

    // Act
    $line->fill([
        'direction' => BudgetDirection::Expense,
        'category' => BudgetCategory::Talent,
        'description' => 'Talent fee',
        'counterparty' => 'Casey',
        'planned_cents' => 50000,
        'confirmed_cents' => 45000,
        'due_date' => '2026-12-01',
        'notes' => 'Paid on the night',
        'event_budget_id' => 99,
        'archived_at' => Carbon::parse('2026-01-01'),
    ]);

    // Assert
    expect(array_keys($line->getAttributes()))->toEqualCanonicalizing([
        'direction', 'category', 'description', 'counterparty', 'planned_cents', 'confirmed_cents', 'due_date', 'notes',
    ]);
});
