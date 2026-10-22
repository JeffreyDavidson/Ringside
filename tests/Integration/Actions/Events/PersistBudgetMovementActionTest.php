<?php

declare(strict_types=1);
use App\Actions\Events\PersistBudgetMovementAction;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/../../../Fixtures/budget.php';

test('movement persistence requires an authenticated actor even inside an owned transaction', function (): void {
    // Arrange
    $f = budgetFixture();
    $data = budgetMovementData();
    auth()->logout();
    // Act / Assert
    expect(fn () => DB::transaction(fn () => resolve(PersistBudgetMovementAction::class)->handle($f['budget'], $f['line'], $data)))->toThrow(LogicException::class);
    expect($f['line']->movements()->count())->toBe(0);
});

test('a movement records the actor passed in, not the signed-in user', function (): void {
    // Arrange
    $f = budgetFixture();
    $actor = administrator();

    // Act
    $movement = DB::transaction(fn () => resolve(PersistBudgetMovementAction::class)->handle($f['budget'], $f['line'], budgetMovementData(), $actor));

    // Assert
    expect($movement->actor_id)->toBe($actor->id)
        ->and($movement->actor_label)->toBe("{$actor->first_name} {$actor->last_name}");
});
