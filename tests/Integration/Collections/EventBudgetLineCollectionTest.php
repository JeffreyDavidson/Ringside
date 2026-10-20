<?php

declare(strict_types=1);

use App\Collections\EventBudgetLineCollection;
use App\Enums\Events\BudgetDirection;
use App\Models\Events\EventBudget;
use App\Models\Events\EventBudgetLine;

/** @return array{open: EventBudgetLine, openIncome: EventBudgetLine, archivedIncome: EventBudgetLine, archivedExpense: EventBudgetLine, lines: EventBudgetLineCollection} */
function budgetLinesInBothStates(): array
{
    $budget = EventBudget::factory()->create();
    $open = EventBudgetLine::factory()->for($budget, 'budget')->create(['direction' => 'expense', 'description' => 'Open expense']);
    $openIncome = EventBudgetLine::factory()->for($budget, 'budget')->create(['direction' => 'income', 'category' => 'tickets', 'description' => 'Open income']);
    $archivedIncome = EventBudgetLine::factory()->for($budget, 'budget')->create(['direction' => 'income', 'category' => 'tickets', 'description' => 'Archived income', 'archived_at' => now()]);
    $archivedExpense = EventBudgetLine::factory()->for($budget, 'budget')->create(['direction' => 'expense', 'description' => 'Archived expense', 'archived_at' => now()]);

    return [
        'open' => $open,
        'openIncome' => $openIncome,
        'archivedIncome' => $archivedIncome,
        'archivedExpense' => $archivedExpense,
        'lines' => $budget->lines()->get(),
    ];
}

it('is the collection a budget returns for its lines', function (): void {
    // Arrange
    $budget = EventBudget::factory()->create();
    EventBudgetLine::factory()->for($budget, 'budget')->create();

    // Act
    $loaded = EventBudget::query()->with('lines')->findOrFail($budget->id);

    // Assert
    expect($loaded->lines)->toBeInstanceOf(EventBudgetLineCollection::class);
});

it('keeps the owning budget reachable from an eager loaded line', function (): void {
    // Arrange
    $budget = EventBudget::factory()->create();
    EventBudgetLine::factory()->for($budget, 'budget')->create();

    // Act
    $line = EventBudgetLine::query()->with('budget')->firstOrFail();

    // Assert
    expect($line->budget->is($budget))->toBeTrue();
});

it('keeps only the lines that are not archived', function (): void {
    // Arrange
    $fixture = budgetLinesInBothStates();

    // Act
    $unarchived = $fixture['lines']->unarchived();

    // Assert
    expect($unarchived)->toBeInstanceOf(EventBudgetLineCollection::class)
        ->and($unarchived->modelKeys())->toBe([$fixture['open']->id, $fixture['openIncome']->id]);
});

it('keeps only the archived lines', function (): void {
    // Arrange
    $fixture = budgetLinesInBothStates();

    // Act
    $archived = $fixture['lines']->archived();

    // Assert
    expect($archived)->toBeInstanceOf(EventBudgetLineCollection::class)
        ->and($archived->modelKeys())->toBe([$fixture['archivedIncome']->id, $fixture['archivedExpense']->id]);
});

it('keeps only the lines moving money in the given direction', function (BudgetDirection $direction, array $expectedDescriptions): void {
    // Arrange
    $fixture = budgetLinesInBothStates();

    // Act
    $inDirection = $fixture['lines']->inDirection($direction);

    // Assert
    expect($inDirection)->toBeInstanceOf(EventBudgetLineCollection::class)
        ->and($inDirection->pluck('description')->all())->toBe($expectedDescriptions);
})->with([
    'expense' => [BudgetDirection::Expense, ['Open expense', 'Archived expense']],
    'income' => [BudgetDirection::Income, ['Open income', 'Archived income']],
]);

it('chains the filters', function (): void {
    // Arrange
    $fixture = budgetLinesInBothStates();

    // Act
    $lines = $fixture['lines']->unarchived()->inDirection(BudgetDirection::Income);

    // Assert
    expect($lines->modelKeys())->toBe([$fixture['openIncome']->id]);
});
