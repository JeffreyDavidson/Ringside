<?php

declare(strict_types=1);

use App\Models\Storylines\StorylineDevelopment;

test('mass assignment accepts the account of what happened and ignores ownership, source links, snapshots and versions', function (): void {
    // Arrange
    $development = new StorylineDevelopment;

    // Act
    $development->fill([
        'heading' => 'Argument',
        'description' => 'It got heated.',
        'occurred_on' => '2026-10-01',
        'sequence' => 1,
        'promotion_id' => 99,
        'storyline_id' => 98,
        'storyline_step_id' => 97,
        'event_id' => 96,
        'match_id' => 95,
        'segment_id' => 'forged',
        'timezone' => 'Asia/Tokyo',
        'source_context' => ['forged' => true],
        'cast' => [['type' => 'wrestler', 'id' => 1, 'label' => 'Forged']],
        'submission_id' => 'forged',
        'version' => 9,
        'retracted_at' => '2026-01-01 00:00:00',
    ]);

    // Assert
    expect(array_keys($development->getAttributes()))->toEqualCanonicalizing(['heading', 'description', 'occurred_on', 'sequence']);
});

test('casts survive a persisted round trip', function (): void {
    // Arrange
    $cast = [['type' => 'wrestler', 'id' => 7, 'label' => 'Ace']];
    $created = StorylineDevelopment::factory()
        ->create([
            'occurred_on' => '2026-10-01',
            'sequence' => 2,
            'version' => 3,
            'source_context' => ['count' => 2],
            'cast' => $cast,
            'retracted_at' => '2026-10-02 03:04:05',
        ]);

    // Act
    $development = StorylineDevelopment::query()->findOrFail($created->id);

    // Assert
    expect($development->occurred_on->toDateString())->toBe('2026-10-01')
        ->and($development->sequence)->toBe(2)
        ->and($development->version)->toBe(3)
        ->and($development->source_context)->toBe(['count' => 2])
        ->and($development->cast)->toEqual($cast)
        ->and($development->retracted_at?->toDateTimeString())->toBe('2026-10-02 03:04:05');
});
