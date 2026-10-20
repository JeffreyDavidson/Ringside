<?php

declare(strict_types=1);

use App\Enums\Events\BudgetOperation;

it('identifies whether an operation needs a reason', function (BudgetOperation $operation, bool $requiresReason) {
    expect($operation->requiresReason())->toBe($requiresReason);
})->with([
    'save line' => [BudgetOperation::SaveLine, false],
    'archive line' => [BudgetOperation::ArchiveLine, true],
    'record movement' => [BudgetOperation::RecordMovement, false],
    'correct movement' => [BudgetOperation::CorrectMovement, true],
]);

it('identifies whether an operation reads the movement form', function (BudgetOperation $operation, bool $replacing, bool $needsMovementInput) {
    expect($operation->needsMovementInput($replacing))->toBe($needsMovementInput);
})->with([
    'save line' => [BudgetOperation::SaveLine, false, false],
    'save line replacing' => [BudgetOperation::SaveLine, true, false],
    'archive line' => [BudgetOperation::ArchiveLine, false, false],
    'archive line replacing' => [BudgetOperation::ArchiveLine, true, false],
    'record movement' => [BudgetOperation::RecordMovement, false, true],
    'record movement replacing' => [BudgetOperation::RecordMovement, true, true],
    'correct movement voiding only' => [BudgetOperation::CorrectMovement, false, false],
    'correct movement with replacement' => [BudgetOperation::CorrectMovement, true, true],
]);
