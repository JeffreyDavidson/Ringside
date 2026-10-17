<?php

declare(strict_types=1);

use App\Enums\Matches\MatchFinish;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Queries\Matches\BookingHistoryNoticeSourcesQuery;

test('reads only the requested earlier matches with their recorded flag and event', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    [$first, $second] = Wrestler::factory()
        ->count(2)
        ->for($promotion, 'promotion')
        ->create()
        ->all();
    $past = Event::factory()
        ->for($promotion, 'promotion')
        ->past()
        ->create();
    $recorded = EventMatch::factory()
        ->for($past)
        ->withCompetitors([$first, $second])
        ->create();
    $recorded->update(['match_finish' => MatchFinish::Pinfall, 'winning_side_id' => $recorded->sides()->firstOrFail()->id]);
    $unrequested = EventMatch::factory()
        ->for($past)
        ->withCompetitors([$first, $second])
        ->create();
    $incomplete = EventMatch::factory()
        ->for($past)
        ->withCompetitors([$first, $second])
        ->create();

    // Act
    $matches = resolve(BookingHistoryNoticeSourcesQuery::class)->get($promotion->id, now(), null, [$recorded->id, $incomplete->id]);

    // Assert
    expect($matches->modelKeys())->toEqualCanonicalizing([$recorded->id, $incomplete->id])
        ->and($matches->modelKeys())->not->toContain($unrequested->id)
        ->and($matches->sole('id', $recorded->id)->getAttribute('history_recorded'))->toBe(1)
        ->and($matches->sole('id', $incomplete->id)->getAttribute('history_recorded'))->toBe(0)
        ->and($matches->every(fn (EventMatch $match): bool => $match->relationLoaded('event') && $match->event->relationLoaded('promotion')))->toBeTrue();
});

test('leaves out later, excluded and other promotion matches and returns nothing for no ids', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    [$first, $second] = Wrestler::factory()
        ->count(2)
        ->for($promotion, 'promotion')
        ->create()
        ->all();
    $past = Event::factory()
        ->for($promotion, 'promotion')
        ->past()
        ->create();
    $later = Event::factory()
        ->for($promotion, 'promotion')
        ->create(['date' => now()->addDays(2)]);
    $excluded = EventMatch::factory()
        ->for($past)
        ->withCompetitors([$first, $second])
        ->create();
    $future = EventMatch::factory()
        ->for($later)
        ->withCompetitors([$first, $second])
        ->create();
    $query = resolve(BookingHistoryNoticeSourcesQuery::class);
    $ids = [$excluded->id, $future->id];

    // Act
    $withExclusion = $query->get($promotion->id, now(), $excluded->id, $ids);
    $otherPromotion = $query->get(Promotion::factory()->create()->id, now(), null, [$excluded->id]);
    $none = $query->get($promotion->id, now(), null, []);
    $statements = recordStatements(fn (): mixed => $query->get($promotion->id, now(), null, [$excluded->id]));

    // Assert
    expect($withExclusion)->toBeEmpty()
        ->and($otherPromotion)->toBeEmpty()
        ->and($none)->toBeEmpty()
        ->and($statements)->toHaveCount(3);
});
