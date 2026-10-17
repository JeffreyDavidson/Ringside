<?php

declare(strict_types=1);

use App\Models\Events\EventSponsorCommitmentRevision;
use Illuminate\Database\Eloquent\MassAssignmentException;

test('mass assignment is refused for every column because revisions are immutable audit rows written only by the persist action', function (): void {
    // Arrange
    $revision = new EventSponsorCommitmentRevision;

    // Act / Assert
    expect(fn () => $revision->fill([
        'promotion_id' => 99,
        'commitment_id' => 98,
        'version' => 2,
        'actor_id' => 97,
        'actor_name' => 'Forged',
        'operation' => 'save',
        'reason' => 'Forged',
        'snapshot' => ['forged' => true],
    ]))
        ->toThrow(MassAssignmentException::class);
});
