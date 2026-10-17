<?php

declare(strict_types=1);

use App\Enums\Events\BudgetOperation;
use App\Models\Events\EventBudgetRevision;
use Illuminate\Database\Eloquent\MassAssignmentException;

test('mass assignment is refused for every column because revisions are audit rows written only by the budget action', function (): void {
    // Arrange
    $revision = new EventBudgetRevision;

    // Act / Assert
    expect(fn () => $revision->fill([
        'event_budget_id' => 99,
        'version' => 3,
        'request_key' => 'key',
        'request_hash' => 'hash',
        'operation' => BudgetOperation::SaveLine,
        'actor_id' => 98,
        'reason' => 'Forged',
    ]))
        ->toThrow(MassAssignmentException::class);
});
