<?php

declare(strict_types=1);

use App\Actions\Matches\RecordResultAction;
use App\Data\Matches\MatchResultData;
use App\Enums\MatchFinish;
use App\Exceptions\Matches\InvalidMatchOutcomeException;
use App\Livewire\Events\Modals\FormModal;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Support\Carbon;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;
use function Pest\Livewire\livewire;

beforeEach(function () {
    // The hard-coded event dates below must stay in the future, so pin the clock instead of using the real one.
    travelTo(Carbon::parse('2026-06-01 12:00:00'));
    actingAs(administrator());
});

afterEach(function () {
    app(PromotionContextService::class)->clear();
});

dataset('local event times', [
    'east of UTC' => ['Asia/Tokyo', '2026-03-01T20:00', '2026-03-01 11:00:00'],
    'west of UTC' => ['America/Los_Angeles', '2026-03-01T20:00', '2026-03-02 04:00:00'],
    'UTC' => ['UTC', '2026-03-01T20:00', '2026-03-01 20:00:00'],
    'standard time before the spring change' => ['America/New_York', '2026-03-07T20:00', '2026-03-08 01:00:00'],
    'daylight time after the spring change' => ['America/New_York', '2026-03-08T20:00', '2026-03-09 00:00:00'],
    'time skipped by the spring change' => ['America/New_York', '2026-03-08T02:30', '2026-03-08 07:30:00'],
    'repeated hour of the autumn change' => ['America/New_York', '2026-11-01T01:30', '2026-11-01 05:30:00'],
]);

it('stores the time entered in the promotion zone as UTC and shows it back unchanged', function (string $timezone, string $entered, string $stored) {
    // Arrange
    $promotion = Promotion::factory()->create(['timezone' => $timezone]);

    // Act
    $utc = Promotion::parseLocalTime($promotion, $entered);
    $event = Event::factory()->for($promotion, 'promotion')->create(['date' => $utc]);
    $event->refresh();

    // Assert
    expect($event->date?->toDateTimeString())->toBe($stored)
        ->and($event->local_date?->getTimezone()->getName())->toBe($timezone);
})->with('local event times');

it('reads a stored date back as the entered wall-clock time', function (string $timezone, string $entered) {
    // Arrange
    $promotion = Promotion::factory()->create(['timezone' => $timezone]);
    $event = Event::factory()->for($promotion, 'promotion')->create([
        'date' => Promotion::parseLocalTime($promotion, $entered),
    ]);

    // Act
    $local = $event->refresh()->local_date;

    // Assert
    expect($local?->format('Y-m-d\\TH:i'))->toBe($entered);
})->with([
    'east of UTC' => ['Asia/Tokyo', '2026-03-01T20:00'],
    'west of UTC' => ['America/Los_Angeles', '2026-03-01T20:00'],
    'daylight time' => ['America/New_York', '2026-07-04T20:00'],
]);

it('treats a missing promotion and an unscheduled event as the application time zone', function () {
    // Arrange
    $event = Event::factory()->unscheduled()->create(['promotion_id' => null]);

    // Act
    $parsed = Promotion::parseLocalTime(null, '2026-03-01T20:00');

    // Assert
    expect($parsed->toDateTimeString())->toBe('2026-03-01 20:00:00')
        ->and(Promotion::toLocalTime(null, $parsed)->format('H:i'))->toBe('20:00')
        ->and($event->local_date)->toBeNull();
});

it('defaults existing and new promotions to UTC', function () {
    // Arrange
    $promotion = Promotion::query()->create(['name' => 'Legacy', 'slug' => 'legacy']);

    // Act
    $timezone = $promotion->refresh()->timezone;

    // Assert
    expect($timezone)->toBe('UTC');
});

it('saves an event date entered in the current promotion zone as UTC', function () {
    // Arrange
    $promotion = Promotion::factory()->create(['timezone' => 'America/Los_Angeles']);
    $context = app(PromotionContextService::class);
    $context->set($promotion);
    $context->enforce();

    // Act
    livewire(FormModal::class)
        ->call('openModal')
        ->set([
            'form.name' => 'West Coast Show',
            'form.date' => '2027-01-15T20:00',
            'form.venue_id' => null,
        ])
        ->call('save')
        ->assertHasNoErrors();

    // Assert
    $event = Event::query()->whereName('West Coast Show')->firstOrFail();
    expect($event->date?->toDateTimeString())->toBe('2027-01-16 04:00:00');
});

it('fills the edit form with the date in the promotion zone', function () {
    // Arrange
    $promotion = Promotion::factory()->create(['timezone' => 'Asia/Tokyo']);
    $event = Event::factory()->for($promotion, 'promotion')->create([
        'date' => Promotion::parseLocalTime($promotion, '2027-01-15T20:00'),
    ]);

    // Act
    $modal = livewire(FormModal::class)->call('openModal', $event->id);

    // Assert
    $modal->assertSet('form.date', '2027-01-15T20:00');
});

it('reads a rescheduled date in the event promotion zone', function () {
    // Arrange
    $promotion = Promotion::factory()->create(['timezone' => 'Asia/Tokyo']);
    $event = Event::factory()->for($promotion, 'promotion')->create([
        'date' => Promotion::parseLocalTime($promotion, '2027-01-15T20:00'),
    ]);

    // Act
    livewire(FormModal::class)
        ->call('openModal', $event->id)
        ->set('form.date', '2027-01-16T21:00')
        ->call('save')
        ->assertHasNoErrors();

    // Assert
    expect($event->refresh()->date?->toDateTimeString())->toBe('2027-01-16 12:00:00');
});

it('opens the result gate at the local start time of the event', function () {
    // Arrange
    $promotion = Promotion::factory()->create(['timezone' => 'America/Los_Angeles']);
    $startsAt = Promotion::parseLocalTime($promotion, '2026-06-01T20:00');
    $match = EventMatch::factory()
        ->for(Event::factory()->for($promotion, 'promotion')->create(['date' => $startsAt]))
        ->create();
    $record = fn () => resolve(RecordResultAction::class)->handle($match, new MatchResultData(MatchFinish::TimeLimitDraw, null, collect()));

    // Act
    travelTo($startsAt->copy()->subMinute());

    // Assert
    expect($record)->toThrow(
        InvalidMatchOutcomeException::class,
        'A result cannot be recorded for an event that has not taken place yet.',
    );

    // Act
    travelTo($startsAt);
    $record();

    // Assert
    expect($match->refresh()->match_finish)->toBe(MatchFinish::TimeLimitDraw);
});

describe('clock changes', function () {
    beforeEach(function () {
        travelTo('2026-09-01 12:00:00');
    });

    it('detects a local time that the spring change skips', function (string $entered, bool $exists) {
        // Arrange
        $promotion = Promotion::factory()->create(['timezone' => 'America/New_York']);

        // Act
        $result = Promotion::localTimeExists($promotion, $entered);

        // Assert
        expect($result)->toBe($exists);
    })->with([
        'inside the skipped hour' => ['2026-03-08T02:30', false],
        'start of the skipped hour' => ['2026-03-08T02:00', false],
        'just before the skipped hour' => ['2026-03-08T01:59', true],
        'first minute after the skipped hour' => ['2026-03-08T03:00', true],
        'repeated autumn hour' => ['2026-11-01T01:30', true],
        'with seconds' => ['2026-03-08T02:30:00', false],
    ]);

    it('rejects creating an event at a time the promotion zone skips', function () {
        // Arrange
        $promotion = Promotion::factory()->create(['timezone' => 'America/New_York']);
        $context = app(PromotionContextService::class);
        $context->set($promotion);
        $context->enforce();

        // Act
        $modal = livewire(FormModal::class)
            ->call('openModal')
            ->set([
                'form.name' => 'Spring Forward Show',
                'form.date' => '2027-03-14T02:30',
                'form.venue_id' => null,
            ])
            ->call('save');

        // Assert
        $modal->assertHasErrors(['form.date'])
            ->assertSee('That time does not exist in America/New_York because the clocks move forward then. Choose a different time.');
        expect(Event::query()->whereName('Spring Forward Show')->exists())->toBeFalse();
    });

    it('rejects rescheduling an event to a time the promotion zone skips', function () {
        // Arrange
        $promotion = Promotion::factory()->create(['timezone' => 'America/New_York']);
        $event = Event::factory()->for($promotion, 'promotion')->create([
            'date' => Promotion::parseLocalTime($promotion, '2027-03-07T20:00'),
        ]);

        // Act
        $modal = livewire(FormModal::class)
            ->call('openModal', $event->id)
            ->set('form.date', '2027-03-14T02:30')
            ->call('save');

        // Assert
        $modal->assertHasErrors(['form.date']);
        expect($event->refresh()->date?->toDateTimeString())->toBe('2027-03-08 01:00:00');
    });

    it('keeps the stored instant when a name-only edit hits the second occurrence of a repeated hour', function () {
        // Arrange: 06:30 UTC is 01:30 EST, the second time the New York clock shows 01:30 on 1 November 2026.
        $promotion = Promotion::factory()->create(['timezone' => 'America/New_York']);
        $event = Event::factory()->for($promotion, 'promotion')->create([
            'name' => 'Fall Back Show',
            'date' => '2026-11-01 06:30:00',
        ]);

        // Act
        livewire(FormModal::class)
            ->call('openModal', $event->id)
            ->assertSet('form.date', '2026-11-01T01:30')
            ->set('form.name', 'Fall Back Spectacular')
            ->call('save')
            ->assertHasNoErrors();

        // Assert
        $event->refresh();
        expect($event->name)->toBe('Fall Back Spectacular')
            ->and($event->date?->toDateTimeString())->toBe('2026-11-01 06:30:00');
    });

    it('lets a name-only edit of a past event in the repeated hour pass the reschedule check', function () {
        // Arrange
        $promotion = Promotion::factory()->create(['timezone' => 'America/New_York']);
        $event = Event::factory()->for($promotion, 'promotion')->create(['date' => '2026-11-01 06:30:00']);
        travelTo('2026-12-01 12:00:00');

        // Act
        livewire(FormModal::class)
            ->call('openModal', $event->id)
            ->set('form.name', 'Renamed After The Fact')
            ->call('save')
            ->assertHasNoErrors();

        // Assert
        expect($event->refresh()->name)->toBe('Renamed After The Fact')
            ->and($event->date?->toDateTimeString())->toBe('2026-11-01 06:30:00');
    });

    it('reads a changed date in the repeated hour as the first occurrence', function () {
        // Arrange
        $promotion = Promotion::factory()->create(['timezone' => 'America/New_York']);
        $event = Event::factory()->for($promotion, 'promotion')->create(['date' => '2026-11-01 15:00:00']);

        // Act
        livewire(FormModal::class)
            ->call('openModal', $event->id)
            ->set('form.date', '2026-11-01T01:30')
            ->call('save')
            ->assertHasNoErrors();

        // Assert
        expect($event->refresh()->date?->toDateTimeString())->toBe('2026-11-01 05:30:00');
    });
});
