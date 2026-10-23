<?php

declare(strict_types=1);

use App\Enums\Matches\MatchFinish;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Queries\Matches\BookingHistoryNoticePairsQuery;
use App\ValueObjects\CompetitorSelection;
use Illuminate\Database\Query\Builder;

function noticePairsSelection(Wrestler $first, Wrestler ...$others): CompetitorSelection
{
    $selection = [['type' => $first->getMorphClass(), 'id' => $first->id, 'side' => 0]];

    foreach (array_values($others) as $index => $other) {
        $selection[] = ['type' => $other->getMorphClass(), 'id' => $other->id, 'side' => $index + 1];
    }

    return new CompetitorSelection($selection);
}

test('prepares one row per opposing pair with its counts, newest pair first, for the caller to page', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    [$subject, $older, $newer] = Wrestler::factory()
        ->count(3)
        ->for($promotion, 'promotion')
        ->create()
        ->all();
    $recentEvent = Event::factory()
        ->for($promotion, 'promotion')
        ->create(['date' => now()->subDays(2)]);
    $oldEvent = Event::factory()
        ->for($promotion, 'promotion')
        ->create(['date' => now()->subDays(40)]);
    $againstOlder = EventMatch::factory()
        ->for($oldEvent)
        ->withCompetitors([$subject, $older])
        ->create();
    $againstOlder->update(['match_finish' => MatchFinish::Pinfall, 'winning_side_id' => $againstOlder->sides()->firstOrFail()->id]);
    $againstNewer = EventMatch::factory()
        ->for($recentEvent)
        ->withCompetitors([$subject, $newer])
        ->create();
    EventMatch::factory()
        ->for($recentEvent)
        ->withCompetitors([$subject, $newer])
        ->create();
    $selection = noticePairsSelection($subject, $older, $newer);

    // Act
    $pairs = resolve(BookingHistoryNoticePairsQuery::class)->get($promotion->id, now(), null, $selection, now()->subDays(90));
    $rows = $pairs->get();

    // Assert
    expect($pairs)->toBeInstanceOf(Builder::class)
        ->and($rows)->toHaveCount(2)
        ->and($rows->map(fn (stdClass $row): array => [(int) $row->recorded, (int) $row->incomplete])->all())->toBe([[0, 2], [1, 0]])
        ->and($rows->pluck('latest_match_id')->map(fn (mixed $id): int => is_numeric($id) ? (int) $id : 0)->all())->toBe([$againstNewer->id, $againstOlder->id])
        ->and($pairs->getCountForPagination())->toBe(2);
});

test('keeps only pairs that met since the cut-off before the reference and not through the excluded match', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    [$subject, $opponent] = Wrestler::factory()
        ->count(2)
        ->for($promotion, 'promotion')
        ->create()
        ->all();
    $tooOld = Event::factory()
        ->for($promotion, 'promotion')
        ->create(['date' => now()->subDays(120)]);
    $later = Event::factory()
        ->for($promotion, 'promotion')
        ->create(['date' => now()->addDays(2)]);
    $recent = Event::factory()
        ->for($promotion, 'promotion')
        ->create(['date' => now()->subDays(5)]);

    foreach ([$tooOld, $later] as $event) {
        EventMatch::factory()
            ->for($event)
            ->withCompetitors([$subject, $opponent])
            ->create();
    }
    $excluded = EventMatch::factory()
        ->for($recent)
        ->withCompetitors([$subject, $opponent])
        ->create();
    $selection = noticePairsSelection($subject, $opponent);
    $query = resolve(BookingHistoryNoticePairsQuery::class);

    // Act
    $pairs = $query->get($promotion->id, now(), $excluded->id, $selection, now()->subDays(90))->get();
    $otherPromotion = $query->get(Promotion::factory()->create()->id, now(), null, $selection, now()->subDays(90))->get();
    $statements = recordStatements(fn (): mixed => $query->get($promotion->id, now(), null, $selection, now()->subDays(90))->get());

    // Assert
    expect($pairs)->toBeEmpty()
        ->and($otherPromotion)->toBeEmpty()
        ->and($statements)->toHaveCount(1);
});
