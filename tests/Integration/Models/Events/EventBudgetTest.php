<?php

declare(strict_types=1);

use App\Models\Events\EventBudget;
use Illuminate\Database\Eloquent\MassAssignmentException;

test('mass assignment is refused for every column because the budget is written only by the budget action', function (): void {
    // Arrange
    $budget = new EventBudget;

    // Act / Assert
    expect(fn () => $budget->fill(['event_id' => 99, 'currency' => 'EUR', 'timezone' => 'Europe/Paris', 'version' => 7]))
        ->toThrow(MassAssignmentException::class);
});
