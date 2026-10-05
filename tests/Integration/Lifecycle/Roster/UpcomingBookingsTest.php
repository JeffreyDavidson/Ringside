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
use Illuminate\Support\Carbon;

use function Pest\Laravel\travelTo;

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
    // The hard-coded event dates below must stay in the future, so pin the clock instead of using the real one.
    beforeEach(fn () => travelTo(Carbon::parse('2026-06-01 12:00:00')));

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
