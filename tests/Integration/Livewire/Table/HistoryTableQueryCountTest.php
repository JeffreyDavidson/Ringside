<?php

declare(strict_types=1);

use App\Livewire\Referees\Tables\PreviousMatches as RefereePreviousMatches;
use App\Livewire\TagTeams\Tables\PreviousMatches as TagTeamPreviousMatches;
use App\Livewire\Venues\Tables\PreviousEvents;
use App\Livewire\Wrestlers\Tables\PreviousMatches as WrestlerPreviousMatches;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Database\Factories\Matches\MatchFactory;
use Illuminate\Database\Eloquent\Model;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

/**
 * A past event in a promotion of its own, so a lazy load of the event's promotion cannot be served from a shared model.
 */
function pastEventInOwnPromotion(): Event
{
    return Event::factory()->past()->for(Promotion::factory(), 'promotion')->create();
}

dataset('previous matches tables', [
    'wrestlers' => [
        WrestlerPreviousMatches::class,
        'wrestlerId',
        fn (): Model => Wrestler::factory()->create(),
        fn (MatchFactory $match, Model $subject): EventMatch => $match
            ->withCompetitors([$subject, Wrestler::factory()->create()])
            ->createOne(),
    ],
    'tag teams' => [
        TagTeamPreviousMatches::class,
        'tagTeamId',
        fn (): Model => TagTeam::factory()->create(),
        fn (MatchFactory $match, Model $subject): EventMatch => $match
            ->withCompetitors([$subject, TagTeam::factory()->create()])
            ->createOne(),
    ],
    'referees' => [
        RefereePreviousMatches::class,
        'refereeId',
        fn (): Model => Referee::factory()->create(),
        function (MatchFactory $match, Referee $subject): EventMatch {
            $eventMatch = $match->createOne();
            $eventMatch->referees()->attach($subject);

            return $eventMatch;
        },
    ],
]);

describe('history table query counts', function (): void {
    it('lists previous matches in the same number of queries however many rows there are', function (
        string $table,
        string $parameter,
        Closure $createSubject,
        Closure $bookMatch,
    ): void {
        // Arrange
        $subject = $createSubject();
        $bookMatch(EventMatch::factory()->for(pastEventInOwnPromotion()), $subject);
        $queriesWithOneRow = recordStatements(fn () => livewire($table, [$parameter => $subject->getKey()]));
        collect(range(1, 9))->each(fn (): EventMatch => $bookMatch(EventMatch::factory()->for(pastEventInOwnPromotion()), $subject));

        // Act
        $queriesWithTenRows = recordStatements(fn () => livewire($table, [$parameter => $subject->getKey()]));

        // Assert
        expect($queriesWithTenRows)->toHaveSameSize($queriesWithOneRow);
    })->with('previous matches tables');

    it('lists a venue previous events in the same number of queries however many rows there are', function (): void {
        // Arrange
        $venue = Venue::factory()->create();
        Event::factory()->past()->atVenue($venue)->for(Promotion::factory(), 'promotion')->create();
        $queriesWithOneRow = recordStatements(fn () => livewire(PreviousEvents::class, ['venueId' => $venue->id]));
        collect(range(1, 9))->each(fn (): Event => Event::factory()->past()->atVenue($venue)->for(Promotion::factory(), 'promotion')->create());

        // Act
        $queriesWithTenRows = recordStatements(fn () => livewire(PreviousEvents::class, ['venueId' => $venue->id]));

        // Assert
        expect($queriesWithTenRows)->toHaveSameSize($queriesWithOneRow);
    });
});
