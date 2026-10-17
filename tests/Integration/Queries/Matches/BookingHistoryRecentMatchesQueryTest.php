<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Queries\Matches\BookingHistoryRecentMatchesQuery;
use Illuminate\Database\Eloquent\Collection;

test('loads the latest earlier matches of each subject, newest event first, up to the limit', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    [$subject, $opponent] = Wrestler::factory()
        ->count(2)
        ->for($promotion, 'promotion')
        ->create()
        ->all();
    $reference = now();
    $oldest = Event::factory()
        ->for($promotion, 'promotion')
        ->create(['date' => now()->subDays(30)]);
    $newest = Event::factory()
        ->for($promotion, 'promotion')
        ->create(['date' => now()->subDays(2)]);
    $middle = Event::factory()
        ->for($promotion, 'promotion')
        ->create(['date' => now()->subDays(10)]);
    $matches = [];
    foreach ([$oldest, $newest, $middle] as $event) {
        $matches[$event->id] = EventMatch::factory()
            ->for($event)
            ->withCompetitors([$subject, $opponent])
            ->create();
    }

    // Act
    $loaded = resolve(BookingHistoryRecentMatchesQuery::class)->get(
        new Collection([$subject]), $promotion->id, $reference, null, 2,
    );

    // Assert
    expect($loaded->sole()->matches->modelKeys())->toBe([$matches[$newest->id]->id, $matches[$middle->id]->id])
        ->and($loaded->sole()->matches->every(fn (EventMatch $match): bool => $match->relationLoaded('event')))->toBeTrue();
});

test('orders events on the same date by the higher event id first, then by match number', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    [$subject, $opponent] = Wrestler::factory()
        ->count(2)
        ->for($promotion, 'promotion')
        ->create()
        ->all();
    $date = now()->subDays(4);
    $earlierEvent = Event::factory()
        ->for($promotion, 'promotion')
        ->create(['date' => $date]);
    $laterEvent = Event::factory()
        ->for($promotion, 'promotion')
        ->create(['date' => $date]);
    $secondBout = EventMatch::factory()
        ->for($earlierEvent)
        ->withCompetitors([$subject, $opponent])
        ->create(['match_number' => 2]);
    $firstBout = EventMatch::factory()
        ->for($earlierEvent)
        ->withCompetitors([$subject, $opponent])
        ->create(['match_number' => 1]);
    $otherEventBout = EventMatch::factory()
        ->for($laterEvent)
        ->withCompetitors([$subject, $opponent])
        ->create(['match_number' => 1]);

    // Act
    $loaded = resolve(BookingHistoryRecentMatchesQuery::class)->get(
        new Collection([$subject]), $promotion->id, now(), null, 5,
    );

    // Assert
    expect($loaded->sole()->matches->modelKeys())->toBe([$otherEventBout->id, $firstBout->id, $secondBout->id]);
});

test('leaves out later events, deleted matches, the excluded match and other promotions', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    [$subject, $opponent] = Wrestler::factory()
        ->count(2)
        ->for($promotion, 'promotion')
        ->create()
        ->all();
    $past = Event::factory()
        ->for($promotion, 'promotion')
        ->create(['date' => now()->subDays(5)]);
    $later = Event::factory()
        ->for($promotion, 'promotion')
        ->create(['date' => now()->addDays(5)]);
    $kept = EventMatch::factory()
        ->for($past)
        ->withCompetitors([$subject, $opponent])
        ->create();
    $excluded = EventMatch::factory()
        ->for($past)
        ->withCompetitors([$subject, $opponent])
        ->create();
    EventMatch::factory()
        ->for($past)
        ->withCompetitors([$subject, $opponent])
        ->trashed()
        ->create();
    EventMatch::factory()
        ->for($later)
        ->withCompetitors([$subject, $opponent])
        ->create();

    // Act
    $loaded = resolve(BookingHistoryRecentMatchesQuery::class)->get(
        new Collection([$subject]), $promotion->id, now(), $excluded->id, 5,
    );
    $otherPromotion = resolve(BookingHistoryRecentMatchesQuery::class)->get(
        new Collection([Wrestler::query()->findOrFail($subject->id)]), Promotion::factory()->create()->id, now(), null, 5,
    );

    // Assert
    expect($loaded->sole()->matches->modelKeys())->toBe([$kept->id])
        ->and($otherPromotion->sole()->matches)->toBeEmpty();
});

test('loads the matches of every subject with one query however many subjects are given', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $wrestlers = Wrestler::factory()
        ->count(3)
        ->for($promotion, 'promotion')
        ->create();
    $past = Event::factory()
        ->for($promotion, 'promotion')
        ->past()
        ->create();
    EventMatch::factory()
        ->for($past)
        ->withCompetitors($wrestlers->all())
        ->create();
    $query = resolve(BookingHistoryRecentMatchesQuery::class);
    $one = recordStatements(fn (): mixed => $query->get(new Collection([$wrestlers->firstOrFail()]), $promotion->id, now(), null, 5));

    // Act
    $many = recordStatements(fn (): mixed => $query->get(new Collection($wrestlers->all()), $promotion->id, now(), null, 5));

    // Assert
    expect($many)->toHaveSameSize($one);
});
