<?php

declare(strict_types=1);
use App\Actions\Events\ArchiveBudgetLineAction;
use App\Exceptions\Events\BudgetException;
use App\Models\Events\EventBudgetMovement;
use App\ViewModels\Events\EventBudgetViewModel;

require_once __DIR__.'/../../../Fixtures/budget.php';

test('plan-only lines archive with history and leave active totals', function (): void {
    // Arrange
    $f = budgetFixture(['confirmed_cents' => null]);
    $key = budgetKey();
    $action = resolve(ArchiveBudgetLineAction::class);
    // Act
    $action->handle($f['event'], 0, $key, $f['line']->id, 'No longer needed');
    $action->handle($f['event'], 0, $key, $f['line']->id, 'No longer needed');
    // Assert
    expect($f['line']->refresh()->archived_at)->not->toBeNull()->and($f['budget']->revisions()->count())->toBe(1);
    expect(fn () => $action->handle($f['event'], 1, budgetKey(), $f['line']->id, 'Again'))->toThrow(BudgetException::class);
    $data = resolve(EventBudgetViewModel::class)->data($f['event']);
    expect($data['totals']['count'])->toBe(0)->and($data['totals']['balanced'])->toBeFalse()->and($data['archived'])->toHaveCount(1);
});

test('lines with an obligation or even voided movements cannot be hidden by archiving', function (string $case): void {
    // Arrange
    $f = budgetFixture(['confirmed_cents' => $case === 'obligation' ? 1 : 0]);
    if ($case === 'movement') {
        EventBudgetMovement::factory()->for($f['line'], 'line')->create(['voided_at' => now()]);
    }
    // Act / Assert
    expect(fn () => resolve(ArchiveBudgetLineAction::class)->handle($f['event'], 0, budgetKey(), $f['line']->id, 'Hide it'))->toThrow(BudgetException::class);
    expect($f['line']->refresh()->archived_at)->toBeNull()->and($f['budget']->revisions()->count())->toBe(0);
})->with(['obligation', 'movement']);
