<?php

declare(strict_types=1);

use App\Models\Storylines\StorylineParticipant;

test('mass assignment accepts the role and dates and ignores ownership, identity and the label snapshot', function (): void {
    // Arrange
    $participant = new StorylineParticipant;

    // Act
    $participant->fill([
        'role' => 'Champion',
        'joined_on' => '2026-01-01',
        'left_on' => '2026-02-01',
        'promotion_id' => 99,
        'storyline_id' => 98,
        'participant_type' => 'wrestler',
        'participant_id' => 97,
        'label' => 'Forged',
    ]);

    // Assert
    expect(array_keys($participant->getAttributes()))->toEqualCanonicalizing(['role', 'joined_on', 'left_on']);
});

test('date casts survive a persisted round trip', function (): void {
    // Arrange
    $created = StorylineParticipant::factory()
        ->create([
            'joined_on' => '2026-01-01',
            'left_on' => '2026-02-03',
        ]);

    // Act
    $participant = StorylineParticipant::query()->findOrFail($created->id);

    // Assert
    expect($participant->joined_on->toDateString())->toBe('2026-01-01')
        ->and($participant->left_on?->toDateString())->toBe('2026-02-03');
});
