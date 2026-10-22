<?php

declare(strict_types=1);

use App\Enums\Storylines\StorylineRevisionOperation;
use App\Enums\Storylines\StorylineRevisionRecord;
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
        'record_type' => 'step',
        'record_id' => 96,
        'reason' => 'Forged',
        'before' => [],
        'after' => [],
    ]))
        ->toThrow(MassAssignmentException::class);
});

test('the operation is cast to its enum and the stored string is unchanged', function (): void {
    // Arrange
    $created = StorylineRevision::factory()
        ->create(['operation' => StorylineRevisionOperation::DevelopmentRetractedReopen]);

    // Act
    $revision = StorylineRevision::query()->findOrFail($created->id);

    // Assert
    expect($revision->operation)->toBe(StorylineRevisionOperation::DevelopmentRetractedReopen)
        ->and($revision->getRawOriginal('operation'))->toBe('development_retracted_reopen');
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

test('the record type is cast to its enum and the stored string is the short name', function (): void {
    // Arrange
    $created = StorylineRevision::factory()
        ->create(['record_type' => StorylineRevisionRecord::Participant]);

    // Act
    $revision = StorylineRevision::query()->findOrFail($created->id);

    // Assert
    expect($revision->record_type)->toBe(StorylineRevisionRecord::Participant)
        ->and($revision->getRawOriginal('record_type'))->toBe('participant');
});
