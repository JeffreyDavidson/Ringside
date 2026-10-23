<?php

declare(strict_types=1);

use App\Enums\Matches\MatchFinish;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Queries\Matches\BookingHistoryLatestRecordedDatesQuery;
use App\ValueObjects\CompetitorSelection;

test('reads the latest recorded earlier date of each selected competitor and skips unrecorded and unselected ones', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    [$first, $second, $bystander] = Wrestler::factory()
        ->count(3)
        ->for($promotion, 'promotion')
        ->create()
        ->all();
    $oldEvent = Event::factory()
        ->for($promotion, 'promotion')
        ->create(['date' => now()->subDays(20)]);
    $recentEvent = Event::factory()
        ->for($promotion, 'promotion')
        ->create(['date' => now()->subDays(3)]);
    $unrecordedEvent = Event::factory()
        ->for($promotion, 'promotion')
        ->create(['date' => now()->subDay()]);

    foreach ([$recentEvent, $oldEvent] as $event) {
        $match = EventMatch::factory()
            ->for($event)
            ->withCompetitors([$first, $bystander])
            ->create();
        $match->update(['match_finish' => MatchFinish::Pinfall, 'winning_side_id' => $match->sides()->firstOrFail()->id]);
    }

    EventMatch::factory()
        ->for($unrecordedEvent)
        ->withCompetitors([$first, $second])
        ->create();
    $selection = new CompetitorSelection([
        ['type' => $first->getMorphClass(), 'id' => $first->id, 'side' => 0],
        ['type' => $second->getMorphClass(), 'id' => $second->id, 'side' => 1],
    ]);

    // Act
    $rows = resolve(BookingHistoryLatestRecordedDatesQuery::class)->get($promotion->id, now(), null, $selection);

    // Assert
    expect($rows)->toHaveCount(1)
        ->and([$rows->sole()->competitor_type, (int) $rows->sole()->competitor_id])->toBe([$first->getMorphClass(), $first->id])
        ->and(Carbon\Carbon::parse($rows->sole()->latest_date)->toDateString())->toBe(now()->subDays(3)->toDateString());
});

test('leaves out later events, the excluded match and other promotions, and returns nothing without history', function (): void {
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
        ->create(['date' => now()->addDays(4)]);

    foreach ([$past, $later] as $event) {
        $match = EventMatch::factory()
            ->for($event)
            ->withCompetitors([$first, $second])
            ->create();
        $match->update(['match_finish' => MatchFinish::NoDecision]);
    }

    $selection = new CompetitorSelection([
        ['type' => $first->getMorphClass(), 'id' => $first->id, 'side' => 0],
        ['type' => $second->getMorphClass(), 'id' => $second->id, 'side' => 1],
    ]);
    $excludedId = $past->matches()->firstOrFail()->id;
    $query = resolve(BookingHistoryLatestRecordedDatesQuery::class);

    // Act
    $withoutPast = $query->get($promotion->id, now(), $excludedId, $selection);
    $otherPromotion = $query->get(Promotion::factory()->create()->id, now(), null, $selection);
    $statements = recordStatements(fn (): mixed => $query->get($promotion->id, now(), null, $selection));

    // Assert
    expect($withoutPast)->toBeEmpty()
        ->and($otherPromotion)->toBeEmpty()
        ->and($statements)->toHaveCount(1);
});
