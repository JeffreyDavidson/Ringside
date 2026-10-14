<?php

declare(strict_types=1);
use App\Actions\Events\PersistBudgetMovementAction;
use App\Actions\Events\RecordBudgetMovementAction;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/../../../Fixtures/budget.php';

test('movement persistence requires an authenticated actor even inside an owned transaction', function (): void {
    // Arrange
    $f = budgetFixture();
    $data = resolve(RecordBudgetMovementAction::class)->data(budgetPayment());
    auth()->logout();
    // Act / Assert
    expect(fn () => DB::transaction(fn () => resolve(PersistBudgetMovementAction::class)->handle($f['budget'], $f['line'], $data)))->toThrow(LogicException::class);
    expect($f['line']->movements()->count())->toBe(0);
});
