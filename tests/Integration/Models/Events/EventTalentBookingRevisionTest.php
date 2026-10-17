<?php

declare(strict_types=1);

use App\Models\Events\EventTalentBookingRevision;
use Illuminate\Database\Eloquent\MassAssignmentException;

test('mass assignment is refused for every column because revisions are immutable audit rows written only by the booking action', function (): void {
    // Arrange
    $revision = new EventTalentBookingRevision;

    // Act / Assert
    expect(fn () => $revision->fill([
        'booking_id' => 99,
        'version' => 2,
        'operation' => 'agreement',
        'reason' => 'Forged',
        'snapshot' => ['forged' => true],
        'actor_id' => 98,
        'actor_name' => 'Forged',
    ]))
        ->toThrow(MassAssignmentException::class);
});
