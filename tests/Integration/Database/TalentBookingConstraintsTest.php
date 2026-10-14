<?php

declare(strict_types=1);

require_once __DIR__.'/../../Fixtures/talent-booking.php';

use App\Models\Events\EventTalentBooking;
use App\Models\Events\EventTalentBookingRevision;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

test('database constraints preserve history and individual uniqueness', function (string $attempt): void {
    // Arrange
    $fixture = talentBookingFixture();

    // Act / Assert
    expect(fn () => DB::transaction(function () use ($fixture, $attempt): void {
        match ($attempt) {
            'duplicate person' => EventTalentBooking::factory()->create(['event_id' => $fixture['event']->id, 'person_id' => $fixture['person']->id]),
            'duplicate revision' => EventTalentBookingRevision::factory()->create(['booking_id' => $fixture['booking']->id, 'version' => 1]),
            'delete event' => $fixture['event']->forceDelete(),
            'delete booking' => $fixture['booking']->delete(),
            default => throw new InvalidArgumentException('Unknown constraint test'),
        };
    }))->toThrow(QueryException::class);
    expect($fixture['booking']->refresh()->revisions()->count())->toBe(1);
})->with(['duplicate person', 'duplicate revision', 'delete event', 'delete booking']);

test('clearing general activity logs does not remove booking history', function (): void {
    // Arrange
    $fixture = talentBookingFixture();

    // Act
    Activity::query()->delete();

    // Assert
    expect($fixture['booking']->refresh()->revisions()->with('booking.event')->sole()->snapshot['travel_notes'])->toBe('LEAK-TRAVEL-7Q')
        ->and($fixture['booking']->revisions()->with('booking.event')->sole()->booking->event->id)->toBe($fixture['event']->id);
});
