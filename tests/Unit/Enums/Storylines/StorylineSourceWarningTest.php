<?php

declare(strict_types=1);

use App\Enums\Storylines\StorylineSourceWarning;

it('has the values the storyline translations are keyed by', function (): void {
    // Arrange
    $keys = ['deleted_source', 'undated_source', 'removed_segment', 'unplaced_segment', 'skipped_source'];

    // Act
    $values = array_map(fn (StorylineSourceWarning $warning): string => $warning->value, StorylineSourceWarning::cases());

    // Assert
    expect($values)->toBe($keys);
});

it('allows a current save only for an undated or skipped source', function (StorylineSourceWarning $warning, bool $allowed): void {
    // Act
    $result = $warning->allowsCurrentSave();

    // Assert
    expect($result)->toBe($allowed);
})->with([
    'deleted source' => [StorylineSourceWarning::DeletedSource, false],
    'undated source' => [StorylineSourceWarning::UndatedSource, true],
    'removed segment' => [StorylineSourceWarning::RemovedSegment, false],
    'unplaced segment' => [StorylineSourceWarning::UnplacedSegment, false],
    'skipped source' => [StorylineSourceWarning::SkippedSource, true],
]);
