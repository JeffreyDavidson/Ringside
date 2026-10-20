<?php

declare(strict_types=1);

use App\Enums\Storylines\StorylineRevisionOperation;

it('stores the strings revisions have always stored', function (): void {
    // Arrange
    $stored = ['created', 'story_revised', 'story_status', 'step_planned', 'step_revised', 'step_status', 'participant_joined', 'participant_left', 'development_recorded', 'development_corrected', 'development_retracted', 'development_retracted_reopen'];

    // Act
    $values = array_map(fn (StorylineRevisionOperation $operation): string => $operation->value, StorylineRevisionOperation::cases());

    // Assert
    expect($values)->toBe($stored);
});
