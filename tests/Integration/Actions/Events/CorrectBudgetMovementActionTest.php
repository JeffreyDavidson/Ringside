<?php

declare(strict_types=1);
use App\Actions\Events\CorrectBudgetMovementAction;
use App\Exceptions\Events\BudgetException;
use App\Models\Events\EventBudgetMovement;
use App\Services\Events\BudgetSettlementService;

require_once __DIR__.'/../../../Fixtures/budget.php';

test('a recording correction keeps the original and records a replacement atomically', function (): void {
    // Arrange
    $f = budgetFixture();
    $movement = EventBudgetMovement::factory()->for($f['line'], 'line')->create();
    $key = budgetKey();
    $action = resolve(CorrectBudgetMovementAction::class);
    // Act
    $action->handle($f['event'], 0, $key, $f['line']->id, $movement->id, 'Entered wrong amount', budgetMovementData(['amount' => '175.00']));
    $action->handle($f['event'], 0, $key, $f['line']->id, $movement->id, 'Entered wrong amount', budgetMovementData(['amount' => '175.00']));
    // Assert
    expect($movement->refresh()->amount_cents)->toBe(20000)->and($movement->voided_at)->not->toBeNull()
        ->and($f['line']->movements()->where('replaces_id', $movement->id)->firstOrFail()->amount_cents)->toBe(17500)
        ->and(resolve(BudgetSettlementService::class)->net($f['line']->load('movements')))->toBe(17500)->and($f['budget']->revisions()->count())->toBe(1);
});

test('a refused replacement rolls back the void and audit', function (): void {
    // Arrange
    $f = budgetFixture();
    $movement = EventBudgetMovement::factory()->for($f['line'], 'line')->create();
    // Act / Assert
    expect(fn () => resolve(CorrectBudgetMovementAction::class)->handle($f['event'], 0, budgetKey(), $f['line']->id, $movement->id, 'Wrong amount', budgetMovementData(['amount' => '700.00'])))->toThrow(BudgetException::class);
    expect($movement->refresh()->voided_at)->toBeNull()->and($f['line']->movements()->count())->toBe(1)->and($f['budget']->revisions()->count())->toBe(0);
});

test('source payments cannot be voided until their refunds have been voided', function (): void {
    // Arrange
    $f = budgetFixture();
    $source = EventBudgetMovement::factory()->for($f['line'], 'line')->create();
    $refund = EventBudgetMovement::factory()->for($f['line'], 'line')->create(['kind' => 'refund', 'amount_cents' => 5000, 'source_id' => $source->id]);
    $action = resolve(CorrectBudgetMovementAction::class);
    // Act / Assert
    expect(fn () => $action->handle($f['event'], 0, budgetKey(), $f['line']->id, $source->id, 'Wrong source'))->toThrow(BudgetException::class);
    // Act
    $action->handle($f['event'], 0, budgetKey(), $f['line']->id, $refund->id, 'Wrong refund');
    $action->handle($f['event'], 1, budgetKey(), $f['line']->id, $source->id, 'Wrong source');
    // Assert
    expect(resolve(BudgetSettlementService::class)->net($f['line']->load('movements')))->toBe(0);
    expect(fn () => $action->handle($f['event'], 2, budgetKey(), $f['line']->id, $source->id, 'Again'))->toThrow(BudgetException::class);
});

test('movement corrections only reach a movement on the same line', function (): void {
    // Arrange
    $f = budgetFixture();
    $foreign = EventBudgetMovement::factory()->create();
    // Act / Assert
    expect(fn () => resolve(CorrectBudgetMovementAction::class)->handle($f['event'], 0, budgetKey(), $f['line']->id, $foreign->id, 'Wrong record'))->toThrow(BudgetException::class, __('event-budget.movement_missing'));
    expect($foreign->refresh()->voided_at)->toBeNull()->and($f['budget']->revisions()->count())->toBe(0);
});
