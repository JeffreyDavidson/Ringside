<?php

declare(strict_types=1);

use App\Models\Events\EventTalentBooking;
use Illuminate\Support\Carbon;

test('mass assignment accepts the agreed terms and ignores identity, status, version and the confirmed snapshot', function (): void {
    // Arrange
    $booking = new EventTalentBooking;

    // Act
    $booking->fill([
        'fee_minor' => 25000,
        'arrival_at' => '2026-12-01 16:00:00',
        'arrival_timezone' => 'America/New_York',
        'travel_notes' => 'Train',
        'event_id' => 99,
        'person_type' => 'forged',
        'person_id' => 98,
        'person_name' => 'Forged',
        'person_role' => 'Forged',
        'status' => 'confirmed',
        'version' => 8,
        'currency' => 'EUR',
        'terms_need_review' => false,
        'confirmed_snapshot' => ['forged' => true],
    ]);

    // Assert
    expect(array_keys($booking->getAttributes()))->toEqualCanonicalizing([
        'fee_minor', 'arrival_at', 'arrival_timezone', 'travel_notes',
    ])
        ->and($booking->arrival_at)->toEqual(Carbon::parse('2026-12-01 16:00:00'));
});
