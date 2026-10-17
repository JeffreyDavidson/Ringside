<?php

declare(strict_types=1);

use App\Enums\Storylines\StepStatus;
use App\Models\Storylines\StorylineStep;

test('mass assignment accepts the planning details and ignores ownership, source links, status and versions', function (): void {
    // Arrange
    $step = new StorylineStep;

    // Act
    $step->fill([
        'heading' => 'Confrontation',
        'description' => 'They argue backstage.',
        'sequence' => 2,
        'target_on' => '2026-12-01',
        'promotion_id' => 99,
        'storyline_id' => 98,
        'event_id' => 97,
        'match_id' => 96,
        'segment_id' => 'forged',
        'timezone' => 'Asia/Tokyo',
        'target_at' => '2026-12-01 00:00:00',
        'source_context' => ['forged' => true],
        'submission_id' => 'forged',
        'status' => StepStatus::Happened,
        'version' => 9,
    ]);

    // Assert
    expect(array_keys($step->getAttributes()))->toEqualCanonicalizing(['heading', 'description', 'sequence', 'target_on']);
});

test('casts survive a persisted round trip', function (): void {
    // Arrange
    $created = StorylineStep::factory()
        ->create([
            'status' => StepStatus::Postponed,
            'sequence' => 3,
            'version' => 4,
            'target_on' => '2026-12-01',
            'target_at' => '2026-12-01 05:00:00',
            'source_context' => ['warnings' => ['late'], 'count' => 2],
        ]);

    // Act
    $step = StorylineStep::query()->findOrFail($created->id);

    // Assert
    expect($step->status)->toBe(StepStatus::Postponed)
        ->and($step->sequence)->toBe(3)
        ->and($step->version)->toBe(4)
        ->and($step->target_on?->toDateString())->toBe('2026-12-01')
        ->and($step->target_at?->toDateTimeString())->toBe('2026-12-01 05:00:00')
        ->and($step->source_context)->toBe(['warnings' => ['late'], 'count' => 2]);
});
