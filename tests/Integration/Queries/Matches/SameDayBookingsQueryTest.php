<?php

declare(strict_types=1);

use App\Enums\Roster\BookableRosterKind;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Queries\Matches\SameDayBookingsQuery;
use Illuminate\Support\Carbon;

/**
 * @param  list<Wrestler|TagTeam>  $competitors
 */
function sameDayBookingsBook(Event $event, array $competitors, ?Referee $referee = null): EventMatch
{
    $match = EventMatch::factory()
        ->for($event)
        ->withCompetitors($competitors)
        ->create();

    if ($referee instanceof Referee) {
        $match->referees()->attach($referee);
    }

    return $match;
}

test('lists the other shows of the day for the chosen wrestlers, in one statement, whatever the selection size', function (): void {
    // Arrange
    $from = Carbon::parse('2026-10-19 07:00:00', 'UTC');
    $until = Carbon::parse('2026-10-20 07:00:00', 'UTC');
    $booked = Event::factory()
        ->create(['name' => 'Evening Show', 'date' => '2026-10-20 03:00:00']);
    $alsoBooked = Event::factory()
        ->create(['name' => 'Morning Show', 'date' => '2026-10-19 16:00:00']);
    $nextDay = Event::factory()
        ->create(['name' => 'Next Day Show', 'date' => '2026-10-20 18:00:00']);
    $own = Event::factory()
        ->create(['name' => 'Own Show', 'date' => '2026-10-19 20:00:00']);
    $busy = Wrestler::factory()
        ->bookable()
        ->create();
    $onNextDay = Wrestler::factory()
        ->bookable()
        ->create();
    $onOwnShow = Wrestler::factory()
        ->bookable()
        ->create();
    $onDeleted = Wrestler::factory()
        ->bookable()
        ->create();
    $opponent = Wrestler::factory()
        ->bookable()
        ->create();
    sameDayBookingsBook($booked, [$busy, $opponent]);
    sameDayBookingsBook($alsoBooked, [$busy, $opponent]);
    sameDayBookingsBook($nextDay, [$onNextDay, $opponent]);
    sameDayBookingsBook($own, [$onOwnShow, $opponent]);
    $deletedShow = Event::factory()
        ->create(['name' => 'Deleted Show', 'date' => '2026-10-19 18:00:00']);
    sameDayBookingsBook($deletedShow, [$onDeleted, $opponent]);
    $deletedShow->delete();
    $deletedMatchShow = Event::factory()
        ->create(['name' => 'Deleted Match Show', 'date' => '2026-10-19 18:00:00']);
    sameDayBookingsBook($deletedMatchShow, [$onDeleted, $opponent])->delete();
    $query = resolve(SameDayBookingsQuery::class);
    $ids = collect([$busy, $onNextDay, $onOwnShow, $onDeleted])->map(fn (Wrestler $wrestler): int => $wrestler->id);

    // Act
    $rows = $query->get(BookableRosterKind::Wrestlers, $ids, $from, $until, $own->id, null);
    $smallerStatements = recordStatements(fn () => $query->get(BookableRosterKind::Wrestlers, $ids->take(1), $from, $until, $own->id, null));
    $largerStatements = recordStatements(fn () => $query->get(BookableRosterKind::Wrestlers, $ids, $from, $until, $own->id, null));

    // Assert
    expect($rows->map(fn (array $row): array => [$row['person'], $row['event']])->all())->toBe([
        [$busy->name, 'Morning Show'],
        [$busy->name, 'Evening Show'],
    ])
        ->and($smallerStatements)->toHaveCount(1)
        ->and($largerStatements)->toHaveCount(1);
});

test('names the tag team and each current member booked individually on another show that day', function (): void {
    // Arrange
    $from = Carbon::parse('2026-10-19 00:00:00', 'UTC');
    $until = Carbon::parse('2026-10-20 00:00:00', 'UTC');
    $own = Event::factory()
        ->create(['date' => '2026-10-19 20:00:00']);
    $other = Event::factory()
        ->create(['name' => 'Other Show', 'date' => '2026-10-19 14:00:00']);
    $member = Wrestler::factory()
        ->bookable()
        ->create();
    $partner = Wrestler::factory()
        ->bookable()
        ->create();
    $teamed = TagTeam::factory()
        ->bookable()
        ->withCurrentWrestlers([$member, $partner])
        ->create();
    $formerMember = Wrestler::factory()
        ->bookable()
        ->create();
    $teamed->wrestlers()->attach($formerMember, ['joined_at' => now()->subYear(), 'left_at' => now()->subMonth()]);
    $bookedTeam = TagTeam::factory()
        ->bookable()
        ->create();
    $opponent = Wrestler::factory()
        ->bookable()
        ->create();
    sameDayBookingsBook($other, [$member, $opponent]);
    sameDayBookingsBook($other, [$formerMember, $opponent]);
    sameDayBookingsBook($other, [$bookedTeam, $opponent]);

    // Act
    $rows = resolve(SameDayBookingsQuery::class)->get(BookableRosterKind::TagTeams, collect([$teamed->id, $bookedTeam->id]), $from, $until, $own->id, null);

    // Assert
    expect($rows->map(fn (array $row): array => [$row['person'], $row['event']])->all())->toEqualCanonicalizing([
        [$member->name, 'Other Show'],
        [$bookedTeam->name, 'Other Show'],
    ]);
});

test('lists the other shows of the day for the chosen referees', function (): void {
    // Arrange
    $from = Carbon::parse('2026-10-19 00:00:00', 'UTC');
    $until = Carbon::parse('2026-10-20 00:00:00', 'UTC');
    $own = Event::factory()
        ->create(['date' => '2026-10-19 20:00:00']);
    $other = Event::factory()
        ->create(['name' => 'Other Show', 'date' => '2026-10-19 14:00:00']);
    $referee = Referee::factory()
        ->bookable()
        ->create();
    $free = Referee::factory()
        ->bookable()
        ->create();
    $first = Wrestler::factory()
        ->bookable()
        ->create();
    $second = Wrestler::factory()
        ->bookable()
        ->create();
    sameDayBookingsBook($other, [$first, $second], $referee);

    // Act
    $rows = resolve(SameDayBookingsQuery::class)->get(BookableRosterKind::Referees, collect([$referee->id, $free->id]), $from, $until, $own->id, null);

    // Assert
    expect($rows->map(fn (array $row): array => [$row['person'], $row['event']])->all())->toBe([
        [Referee::query()->findOrFail($referee->id)->full_name, 'Other Show'],
    ]);
});

test('reads nothing when no one is chosen', function (): void {
    // Arrange
    $from = Carbon::parse('2026-10-19 00:00:00', 'UTC');

    // Act
    $statements = recordStatements(fn () => resolve(SameDayBookingsQuery::class)->get(BookableRosterKind::Wrestlers, collect(), $from, $from->copy()->addDay(), 0, null));

    // Assert
    expect($statements)->toBeEmpty();
});

test('never names a show of another promotion than the booked one', function (): void {
    // Arrange
    $from = Carbon::parse('2026-10-19 00:00:00', 'UTC');
    $until = Carbon::parse('2026-10-20 00:00:00', 'UTC');
    $promotion = Promotion::factory()
        ->create();
    $otherPromotion = Promotion::factory()
        ->create();
    $own = Event::factory()
        ->for($promotion, 'promotion')
        ->create(['date' => '2026-10-19 20:00:00']);
    $sameSide = Event::factory()
        ->for($promotion, 'promotion')
        ->create(['name' => 'Home Show', 'date' => '2026-10-19 14:00:00']);
    $foreign = Event::factory()
        ->for($otherPromotion, 'promotion')
        ->create(['name' => 'Foreign Show', 'date' => '2026-10-19 15:00:00']);
    $wrestler = Wrestler::factory()
        ->bookable()
        ->for($promotion)
        ->create();
    $opponent = Wrestler::factory()
        ->bookable()
        ->for($promotion)
        ->create();
    sameDayBookingsBook($sameSide, [$wrestler, $opponent]);
    sameDayBookingsBook($foreign, [$wrestler, $opponent]);

    // Act
    $rows = resolve(SameDayBookingsQuery::class)->get(BookableRosterKind::Wrestlers, collect([$wrestler->id]), $from, $until, $own->id, $promotion->id);

    // Assert
    expect($rows->pluck('event')->all())->toBe(['Home Show']);
});
