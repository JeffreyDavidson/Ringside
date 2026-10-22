<?php

declare(strict_types=1);
use App\Actions\Events\PersistBudgetMovementAction;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/../../../Fixtures/budget.php';

test('a movement with nobody signed in records the actor passed in', function (): void {
    // Arrange
    $f = budgetFixture();
    $actor = administrator();
    auth()->logout();

    // Act
    $movement = DB::transaction(fn () => resolve(PersistBudgetMovementAction::class)->handle($f['budget'], $f['line'], budgetMovementData(), $actor));

    // Assert
    expect(auth()->check())->toBeFalse()
        ->and($movement->actor_id)->toBe($actor->id);
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
