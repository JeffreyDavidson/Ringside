<?php

declare(strict_types=1);
use App\Actions\Events\MutateBudgetAction;
use App\Actions\Events\SaveBudgetLineAction;
use App\Enums\Events\BudgetOperation;
use App\Models\Events\EventBudget;

require_once __DIR__.'/../../../Fixtures/budget.php';

test('budget mutation locks the owning event budget and line before the write', function (): void {
    // Arrange
    $f = budgetFixture();
    // Act
    $statements = recordStatements(fn () => resolve(SaveBudgetLineAction::class)->handle($f['event'], 0, budgetKey(), $f['line']->id, budgetLineData(['description' => 'Updated fee'])));
    // Assert
    $eventLock = statementPosition($statements, fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "events"'));
    $budgetLock = statementPosition($statements, fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "event_budgets"'));
    $lineLock = statementPosition($statements, fn (array $statement): bool => $statement['locked'] && str_contains($statement['sql'], 'from "event_budget_lines"'));
    $write = statementPosition($statements, fn (array $statement): bool => str_starts_with($statement['sql'], 'update "event_budget_lines"'));
    expect($eventLock)->toBeLessThan($budgetLock)->and($budgetLock)->toBeLessThan($lineLock)->and($lineLock)->toBeLessThan($write)
        ->and($statements[$eventLock]['bindings'])->toBe([$f['event']->id]);
});

test('a failed financial mutation rolls back both its state and retained history', function (): void {
    // Arrange
    $f = budgetFixture();
    // Act / Assert
    expect(fn () => resolve(MutateBudgetAction::class)->handle($f['event'], 0, budgetKey(), BudgetOperation::SaveLine, ['description' => 'Should roll back'], function (EventBudget $budget): never {
        $line = $budget->lines()->firstOrFail();
        $line->description = 'Should roll back';
        $line->save();
        throw new RuntimeException('Caller failed');
    }))->toThrow(RuntimeException::class);
    expect($f['line']->refresh()->description)->toBe('Talent fee')->and($f['budget']->refresh()->version)->toBe(0)->and($f['budget']->revisions()->count())->toBe(0);
});

test('a budget write without an authenticated actor is refused and leaves no history', function (): void {
    // Arrange
    $f = budgetFixture();
    auth()->logout();

    // Act
    $write = fn () => resolve(MutateBudgetAction::class)->handle($f['event'], 0, budgetKey(), BudgetOperation::SaveLine, [], fn (EventBudget $budget): null => null);

    // Assert
    expect($write)->toThrow(LogicException::class);
    expect($f['budget']->revisions()->count())->toBe(0);
});

test('a budget revision records the actor passed in, not the signed-in user', function (): void {
    // Arrange
    $f = budgetFixture();
    $actor = administrator();

    // Act
    resolve(MutateBudgetAction::class)->handle($f['event'], 0, budgetKey(), BudgetOperation::SaveLine, [], fn (EventBudget $budget): array => ['target' => $f['line']->id, 'before' => [], 'after' => []], $actor);

    // Assert
    $revision = $f['budget']->revisions()->sole();
    expect($revision->actor_id)->toBe($actor->id)
        ->and($revision->actor_label)->toBe("{$actor->first_name} {$actor->last_name}");
});
