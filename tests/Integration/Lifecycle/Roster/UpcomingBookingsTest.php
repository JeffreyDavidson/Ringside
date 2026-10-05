<?php

declare(strict_types=1);

use App\Enums\MatchFinish;
use App\Lifecycle\Roster\UpcomingBookings;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Database\Eloquent\Model;

/**
 * Books a wrestler, tag team or referee in a new match on the given event.
 */
function bookOn(Model $member, Event $event, ?MatchFinish $finish = null): EventMatch
{
    $match = EventMatch::factory()->forEvent($event);

    if ($member instanceof Referee) {
        $created = $match->create(['match_finish' => $finish]);
        $created->referees()->attach($member);

        return $created;
    }

    return $match
        ->withCompetitors([$member, $member instanceof TagTeam ? TagTeam::factory()->create() : Wrestler::factory()->create()])
        ->create(['match_finish' => $finish]);
}

dataset('rosterMembers', [
    'wrestler' => [fn (): Wrestler => Wrestler::factory()->create()],
    'tag team' => [fn (): TagTeam => TagTeam::factory()->create()],
    'referee' => [fn (): Referee => Referee::factory()->create()],
]);

describe('upcoming bookings', function (): void {
    it('lists the events a roster member is booked in ordered by date', function (Closure $makeMember): void {
        // Arrange
        $member = $makeMember();
        $later = Event::factory()->scheduledOn('2026-12-01 19:00:00')->create(['name' => 'Later Show']);
        $sooner = Event::factory()->scheduledOn('2026-11-01 19:00:00')->create(['name' => 'Sooner Show']);
        bookOn($member, $later);
        bookOn($member, $sooner);

        // Act
        $events = resolve(UpcomingBookings::class)->events($member);

        // Assert
        expect($events->pluck('name')->all())->toBe(['Sooner Show', 'Later Show']);
    })->with('rosterMembers');

    it('reports whether a roster member has upcoming bookings', function (Closure $makeMember): void {
        // Arrange
        $booked = $makeMember();
        $unbooked = $makeMember();
        bookOn($booked, Event::factory()->scheduled()->create());
        $bookings = resolve(UpcomingBookings::class);

        // Act
        $bookedExists = $bookings->exist($booked);
        $unbookedExists = $bookings->exist($unbooked);

        // Assert
        expect($bookedExists)->toBeTrue()
            ->and($unbookedExists)->toBeFalse()
            ->and($bookings->events($unbooked))->toBeEmpty();
    })->with('rosterMembers');

    it('ignores resulted matches on past events', function (Closure $makeMember): void {
        // Arrange
        $member = $makeMember();
        bookOn($member, Event::factory()->past()->create(), MatchFinish::Pinfall);
        $bookings = resolve(UpcomingBookings::class);

        // Act
        $exists = $bookings->exist($member);

        // Assert
        expect($exists)->toBeFalse()
            ->and($bookings->events($member))->toBeEmpty();
    })->with('rosterMembers');

    it('ignores deleted matches', function (Closure $makeMember): void {
        // Arrange
        $member = $makeMember();
        bookOn($member, Event::factory()->scheduled()->create())->delete();
        $bookings = resolve(UpcomingBookings::class);

        // Act
        $exists = $bookings->exist($member);

        // Assert
        expect($exists)->toBeFalse()
            ->and($bookings->events($member))->toBeEmpty();
    })->with('rosterMembers');

    it('includes bookings in other promotions', function (Closure $makeMember): void {
        // Arrange
        $current = Promotion::factory()->create();
        $member = $makeMember();
        $member->forceFill(['promotion_id' => $current->id])->save();
        $other = Promotion::factory()->create();
        bookOn($member, Event::factory()->for($other, 'promotion')->scheduledOn('2026-11-01 19:00:00')->create(['name' => 'Other Show']));
        $context = resolve(PromotionContextService::class);
        $context->set($current);
        $context->enforce();
        $bookings = resolve(UpcomingBookings::class);

        // Act
        $exists = $bookings->exist($member);
        $events = $bookings->events($member);
        $context->clear();

        // Assert
        expect($exists)->toBeTrue()
            ->and($events->pluck('name')->all())->toBe(['Other Show']);
    })->with('rosterMembers');
});

describe('bookings broken through a relationship', function (): void {
    it('lists the bookings of a tag team current wrestlers but not of a former member', function (): void {
        // Arrange
        [$current, $former, $partner] = Wrestler::factory()->count(3)->create()->all();
        $tagTeam = TagTeam::factory()->create();
        $tagTeam->wrestlers()->attach($current, ['joined_at' => '2026-01-01 00:00:00', 'left_at' => null]);
        $tagTeam->wrestlers()->attach($former, ['joined_at' => '2025-01-01 00:00:00', 'left_at' => '2025-06-01 00:00:00']);
        bookOn($tagTeam, Event::factory()->scheduledOn('2026-12-01 19:00:00')->create(['name' => 'Team Show']));
        bookOn($current, Event::factory()->scheduledOn('2026-11-01 19:00:00')->create(['name' => 'Singles Show']));
        bookOn($former, Event::factory()->scheduledOn('2026-10-20 19:00:00')->create(['name' => 'Former Member Show']));
        bookOn($partner, Event::factory()->scheduledOn('2026-10-21 19:00:00')->create(['name' => 'Unrelated Show']));

        // Act
        $events = resolve(UpcomingBookings::class)->events($tagTeam);

        // Assert
        expect($events->pluck('name')->all())->toBe(['Singles Show', 'Team Show']);
    });

    it('lists the bookings of the current tag team of a wrestler but not of a former one', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()->create();
        $currentTeam = TagTeam::factory()->create();
        $formerTeam = TagTeam::factory()->create();
        $currentTeam->wrestlers()->attach($wrestler, ['joined_at' => '2026-01-01 00:00:00', 'left_at' => null]);
        $formerTeam->wrestlers()->attach($wrestler, ['joined_at' => '2025-01-01 00:00:00', 'left_at' => '2025-06-01 00:00:00']);
        bookOn($wrestler, Event::factory()->scheduledOn('2026-11-01 19:00:00')->create(['name' => 'Singles Show']));
        bookOn($currentTeam, Event::factory()->scheduledOn('2026-12-01 19:00:00')->create(['name' => 'Team Show']));
        bookOn($formerTeam, Event::factory()->scheduledOn('2026-10-20 19:00:00')->create(['name' => 'Former Team Show']));

        // Act
        $events = resolve(UpcomingBookings::class)->events($wrestler);

        // Assert
        expect($events->pluck('name')->all())->toBe(['Singles Show', 'Team Show']);
    });

    it('lists an event once when the member and a related member share its card', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()->create();
        $tagTeam = TagTeam::factory()->create();
        $tagTeam->wrestlers()->attach($wrestler, ['joined_at' => '2026-01-01 00:00:00', 'left_at' => null]);
        $event = Event::factory()->scheduledOn('2026-12-01 19:00:00')->create(['name' => 'Shared Show']);
        bookOn($wrestler, $event);
        bookOn($tagTeam, $event);

        // Act
        $events = resolve(UpcomingBookings::class)->events($tagTeam);

        // Assert
        expect($events->pluck('name')->all())->toBe(['Shared Show']);
    });

    it('does not widen the existence check used by deletion', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()->create();
        $tagTeam = TagTeam::factory()->create();
        $tagTeam->wrestlers()->attach($wrestler, ['joined_at' => '2026-01-01 00:00:00', 'left_at' => null]);
        bookOn($tagTeam, Event::factory()->scheduledOn('2026-12-01 19:00:00')->create());

        // Act
        $exists = resolve(UpcomingBookings::class)->exist($wrestler);

        // Assert
        expect($exists)->toBeFalse();
    });
});
