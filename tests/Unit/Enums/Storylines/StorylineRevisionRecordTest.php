<?php

declare(strict_types=1);

use App\Enums\Storylines\StorylineRevisionRecord;

it('stores short names that survive a class rename', function (): void {
    // Arrange
    $stored = ['storyline', 'step', 'participant', 'development'];

    // Act
    $values = array_map(fn (StorylineRevisionRecord $record): string => $record->value, StorylineRevisionRecord::cases());

    // Assert
    expect($values)->toBe($stored);
});
