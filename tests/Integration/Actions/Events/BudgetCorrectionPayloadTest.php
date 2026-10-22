<?php

declare(strict_types=1);

use App\Actions\Events\CorrectBudgetMovementAction;
use App\Data\Events\BudgetCorrectionData;
use App\Enums\Events\BudgetOperation;
use App\Models\Events\EventBudgetMovement;

require_once __DIR__.'/../../../Fixtures/budget.php';

test('a correction with a replacement stores the request hash of the original payload shape', function (): void {
    // Arrange
    $f = budgetFixture();
    $movement = EventBudgetMovement::factory()->for($f['line'], 'line')->create();
    $replacement = budgetMovementData(['amount' => '175.00']);
    $expectedPayload = ['line_id' => $f['line']->id, 'movement_id' => $movement->id, 'replacement' => (array) $replacement];
    $expectedHash = hash('sha256', json_encode([BudgetOperation::CorrectMovement->value, $expectedPayload, 'Entered wrong amount'], JSON_THROW_ON_ERROR));

    // Act
    resolve(CorrectBudgetMovementAction::class)->handle($f['event'], 0, budgetKey(), $f['line']->id, new BudgetCorrectionData($movement->id, '  Entered wrong amount ', $replacement), signedInUser());

    // Assert
    expect($f['budget']->revisions()->firstOrFail()->request_hash)->toBe($expectedHash);
});

test('a void without a replacement stores the request hash with a null replacement', function (): void {
    // Arrange
    $f = budgetFixture();
    $movement = EventBudgetMovement::factory()->for($f['line'], 'line')->create();
    $expectedPayload = ['line_id' => $f['line']->id, 'movement_id' => $movement->id, 'replacement' => null];
    $expectedHash = hash('sha256', json_encode([BudgetOperation::CorrectMovement->value, $expectedPayload, 'Wrong record'], JSON_THROW_ON_ERROR));

    // Act
    resolve(CorrectBudgetMovementAction::class)->handle($f['event'], 0, budgetKey(), $f['line']->id, new BudgetCorrectionData($movement->id, 'Wrong record'), signedInUser());

    // Assert
    expect($f['budget']->revisions()->firstOrFail()->request_hash)->toBe($expectedHash);
});
