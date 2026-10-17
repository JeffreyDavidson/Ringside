<?php

declare(strict_types=1);

use App\Enums\Matches\MatchFinish;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Queries\Matches\BookingHistoryDirectRecordQuery;

test('counts the earlier direct meetings seen from the first competitor and ignores other opponents', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    [$first, $second, $third] = Wrestler::factory()
        ->count(3)
        ->for($promotion, 'promotion')
        ->create()
        ->all();
    $past = Event::factory()
        ->for($promotion, 'promotion')
        ->past()
        ->create();
    $won = EventMatch::factory()
        ->for($past)
        ->withCompetitors([$first, $second])
        ->create();
    $won->update(['match_finish' => MatchFinish::Pinfall, 'winning_side_id' => $won->sides()->firstOrFail()->id]);
    $lost = EventMatch::factory()
        ->for($past)
        ->withCompetitors([$second, $first])
        ->create();
    $lost->update(['match_finish' => MatchFinish::Submission, 'winning_side_id' => $lost->sides()->firstOrFail()->id]);
    EventMatch::factory()
        ->for($past)
        ->withCompetitors([$first, $second])
        ->create(['match_finish' => MatchFinish::TimeLimitDraw]);
    EventMatch::factory()
        ->for($past)
        ->withCompetitors([$first, $second])
        ->create();
    $otherOpponent = EventMatch::factory()
        ->for($past)
        ->withCompetitors([$first, $third])
        ->create();
    $otherOpponent->update(['match_finish' => MatchFinish::Pinfall, 'winning_side_id' => $otherOpponent->sides()->firstOrFail()->id]);

    // Act
    $row = resolve(BookingHistoryDirectRecordQuery::class)->get($promotion->id, now(), null, $first, $second);

    // Assert
    expect([
        (int) $row->recorded, (int) $row->incomplete, (int) $row->first_wins, (int) $row->second_wins, (int) $row->draws, (int) $row->neutral,
    ])->toBe([3, 1, 1, 1, 1, 0]);
});

test('returns zero counts without meetings and honours the reference, excluded match and promotion', function (): void {
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
        ->create(['date' => now()->addDays(3)]);
    $excluded = EventMatch::factory()
        ->for($past)
        ->withCompetitors([$first, $second])
        ->create(['match_finish' => MatchFinish::NoDecision]);
    EventMatch::factory()
        ->for($later)
        ->withCompetitors([$first, $second])
        ->create(['match_finish' => MatchFinish::NoDecision]);
    $query = resolve(BookingHistoryDirectRecordQuery::class);

    // Act
    $row = $query->get($promotion->id, now(), $excluded->id, $first, $second);
    $other = $query->get(Promotion::factory()->create()->id, now(), null, $first, $second);
    $statements = recordStatements(fn (): mixed => $query->get($promotion->id, now(), null, $first, $second));

    // Assert
    expect([(int) $row->recorded, (int) $row->incomplete, (int) $row->neutral])->toBe([0, 0, 0])
        ->and((int) $other->recorded)->toBe(0)
        ->and($statements)->toHaveCount(1);
});
