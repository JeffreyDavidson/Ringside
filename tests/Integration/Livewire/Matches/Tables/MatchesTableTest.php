<?php

declare(strict_types=1);

use App\Enums\MatchFinish;
use App\Enums\MatchType;
use App\Livewire\Matches\Tables\MatchesTable;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

describe('rendering', function (): void {
    it('renders an empty state when the event has no matches', function (): void {
        // Arrange
        $event = Event::factory()->create();

        // Act
        $component = livewire(MatchesTable::class, ['eventId' => $event->id]);

        // Assert
        $component
            ->assertSuccessful()
            ->assertSee('No records found.');
    });

    it('renders the match competitors, referees, titles, and empty result', function (): void {
        // Arrange
        $event = Event::factory()->create();
        $wrestler = Wrestler::factory()->create(['name' => 'Singles Wrestler']);
        $tagTeam = TagTeam::factory()->create(['name' => 'Tag Team']);
        $referee = Referee::factory()->create([
            'first_name' => 'Earl',
            'last_name' => 'Hebner',
        ]);
        $title = Title::factory()
            ->tagTeam()
            ->create(['name' => 'World Tag Team Titles']);
        $match = EventMatch::factory()
            ->forEvent($event)
            ->withMatchType(MatchType::TagTeam)
            ->withCompetitors([$wrestler, $tagTeam])
            ->create();
        $match->referees()->attach($referee);
        $match->titles()->attach($title);

        // Act
        $component = livewire(MatchesTable::class, ['eventId' => $event->id]);

        // Assert
        $component
            ->assertSuccessful()
            ->assertSee(MatchType::TagTeam->label())
            ->assertSee('Singles Wrestler')
            ->assertSee('Tag Team')
            ->assertSee('Earl Hebner')
            ->assertSee('World Tag Team Titles')
            ->assertSee('N/A')
            ->assertSeeHtml(route('wrestlers.show', $wrestler))
            ->assertSeeHtml(route('tag-teams.show', $tagTeam))
            ->assertSeeHtml(route('referees.show', $referee))
            ->assertSeeHtml(route('titles.show', $title));
    });

    it('offers result recording and correction for the appropriate matches', function (): void {
        // Arrange
        $event = Event::factory()->create();
        $unresultedMatch = EventMatch::factory()
            ->forEvent($event)
            ->create();
        $resultedMatch = EventMatch::factory()
            ->forEvent($event)
            ->create(['match_finish' => MatchFinish::TimeLimitDraw]);

        // Act
        $component = livewire(MatchesTable::class, ['eventId' => $event->id]);

        // Assert
        $component
            ->assertSee('Record Result')
            ->assertSee('Correct Result')
            ->assertSeeHtml("matchId: {$unresultedMatch->id}")
            ->assertSeeHtml("matchId: {$resultedMatch->id}");
    });
});

describe('search and event scoping', function (): void {
    it('searches matches by type and clears the search', function (): void {
        // Arrange
        $event = Event::factory()->create();
        EventMatch::factory()
            ->forEvent($event)
            ->withMatchType(MatchType::Singles)
            ->create();
        EventMatch::factory()
            ->forEvent($event)
            ->withMatchType(MatchType::TagTeam)
            ->create();
        $component = livewire(MatchesTable::class, ['eventId' => $event->id]);

        // Act
        $component->set('search', 'Singles');

        // Assert
        $component
            ->assertSee('Singles')
            ->assertDontSee('Tag Team');

        // Act
        $component->set('search', '');

        // Assert
        $component
            ->assertSee('Singles')
            ->assertSee('Tag Team');
    });

    it('renders only matches belonging to the selected event', function (): void {
        // Arrange
        $selectedEvent = Event::factory()->create();
        $otherEvent = Event::factory()->create();
        EventMatch::factory()
            ->forEvent($selectedEvent)
            ->withMatchType(MatchType::Singles)
            ->create();
        EventMatch::factory()
            ->forEvent($otherEvent)
            ->withMatchType(MatchType::TagTeam)
            ->create();

        // Act
        $component = livewire(MatchesTable::class, ['eventId' => $selectedEvent->id]);

        // Assert
        $component
            ->assertSee('Singles')
            ->assertDontSee('Tag Team');
    });
});

it('paginates rendered matches using the selected page size', function (): void {
    // Arrange
    $event = Event::factory()->create();
    foreach (
        [
            MatchType::Singles,
            MatchType::TagTeam,
            MatchType::TripleThreat,
            MatchType::Triangle,
            MatchType::Fatal4Way,
            MatchType::BattleRoyal,
        ] as $matchType
    ) {
        EventMatch::factory()
            ->forEvent($event)
            ->withMatchType($matchType)
            ->create();
    }
    $component = livewire(MatchesTable::class, ['eventId' => $event->id]);

    // Act
    $component->set('perPage', 5);

    // Assert
    $component
        ->assertSee('Singles')
        ->assertSee('Tag Team')
        ->assertSee('Triple Threat')
        ->assertSee('Triangle')
        ->assertSee('Fatal 4 Way')
        ->assertDontSee('Battle Royal');

    // Act
    $component->call('nextPage');

    // Assert
    $component
        ->assertSee('Battle Royal')
        ->assertDontSee('Singles');
});

it('forbids users without administrative access', function (string $actor): void {
    // Arrange
    if ($actor === 'guest') {
        Auth::logout();
    } else {
        actingAs(basicUser());
    }

    $event = Event::factory()->create();

    // Act
    $component = livewire(MatchesTable::class, ['eventId' => $event->id]);

    // Assert
    $component->assertForbidden();
})->with([
    'guest' => ['guest'],
    'basic user' => ['basic user'],
]);
