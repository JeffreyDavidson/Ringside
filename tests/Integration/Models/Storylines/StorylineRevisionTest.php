<?php

declare(strict_types=1);

use App\Models\Storylines\StorylineRevision;
use Illuminate\Database\Eloquent\MassAssignmentException;

test('mass assignment is refused for every column because revisions are audit rows written only by the revision action', function (): void {
    // Arrange
    $revision = new StorylineRevision;

    // Act / Assert
    expect(fn () => $revision->fill([
        'storyline_id' => 99,
        'promotion_id' => 98,
        'actor_id' => 97,
        'operation' => 'forged',
        'record_type' => 'StorylineStep',
        'record_id' => 96,
        'reason' => 'Forged',
        'before' => [],
        'after' => [],
    ]))
        ->toThrow(MassAssignmentException::class);
});

test('before and after snapshots survive a persisted round trip', function (): void {
    // Arrange
    $created = StorylineRevision::factory()
        ->create([
            'before' => ['heading' => 'Old', 'sequence' => 1],
            'after' => ['heading' => 'New', 'sequence' => 2],
        ]);

    // Act
    $revision = StorylineRevision::query()->findOrFail($created->id);

    // Assert
    expect($revision->before)->toBe(['heading' => 'Old', 'sequence' => 1])
        ->and($revision->after)->toBe(['heading' => 'New', 'sequence' => 2]);
});
