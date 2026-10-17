<?php

declare(strict_types=1);

use App\Models\Events\EventPublication;
use Illuminate\Support\Carbon;

test('mass assignment accepts the draft and ignores identity, released snapshots and publisher', function (): void {
    // Arrange
    $publication = new EventPublication;

    // Act
    $publication->fill([
        'draft' => ['announcement' => 'Big night'],
        'event_id' => 99,
        'promotion_id' => 98,
        'public_id' => 'forged',
        'slug' => 'forged',
        'version' => 4,
        'card' => ['title' => 'Forged'],
        'results' => ['rows' => []],
        'card_fingerprint' => 'forged',
        'published_at' => Carbon::parse('2026-01-01'),
        'published_by' => 97,
    ]);

    // Assert
    expect(array_keys($publication->getAttributes()))->toBe(['draft']);
});
