<?php

declare(strict_types=1);

use App\Models\Promotions\Promotion;
use App\Models\Roster\Availability\Unavailability;
use App\Models\Roster\Wrestlers\Wrestler;

test('mass assignment accepts the dates and reason and ignores ownership, person and cancellation fields', function (): void {
    // Arrange
    $entry = new Unavailability;

    // Act
    $entry->fill([
        'starts_on' => '2026-10-17',
        'ends_on' => '2026-10-18',
        'reason' => 'Travel',
        'promotion_id' => 99,
        'person_type' => 'wrestler',
        'person_id' => 98,
        'created_by' => 97,
        'cancelled_by' => 96,
        'cancelled_at' => '2026-01-01 00:00:00',
    ]);

    // Assert
    expect(array_keys($entry->getAttributes()))->toEqualCanonicalizing(['starts_on', 'ends_on', 'reason']);
});

test('the cancelled_at cast survives a persisted round trip', function (): void {
    // Arrange
    $person = Wrestler::factory()
        ->for(Promotion::factory())
        ->create();
    $created = Unavailability::factory()
        ->create([
            'person_id' => $person->id,
            'cancelled_at' => '2026-10-02 03:04:05',
        ]);

    // Act
    $entry = Unavailability::query()->findOrFail($created->id);

    // Assert
    expect($entry->cancelled_at?->toDateTimeString())->toBe('2026-10-02 03:04:05')
        ->and($entry->starts_on)->toBe('2026-10-17');
});
