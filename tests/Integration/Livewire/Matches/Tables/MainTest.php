<?php

declare(strict_types=1);

use App\Enums\MatchType;
use App\Livewire\Matches\Tables\Main;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

it('renders an empty state without matches', function (): void {
    // Act
    $component = livewire(Main::class);

    // Assert
    $component
        ->assertSuccessful()
        ->assertSee('No records found.');
});

it('renders matches by newest event and match number', function (): void {
    // Arrange
    $latestEvent = Event::factory()->create(['date' => Date::tomorrow()]);
    $earliestEvent = Event::factory()->create(['date' => Date::yesterday()]);
    $firstMatchWrestler = Wrestler::factory()->create(['name' => 'First Match Wrestler']);
    $secondMatchWrestler = Wrestler::factory()->create(['name' => 'Second Match Wrestler']);
    $earliestMatchWrestler = Wrestler::factory()->create(['name' => 'Earlier Match Wrestler']);
    EventMatch::factory()
        ->forEvent($latestEvent)
        ->withMatchNumber(2)
        ->withCompetitors([$secondMatchWrestler])
        ->create();
    EventMatch::factory()
        ->forEvent($latestEvent)
        ->withMatchNumber(1)
        ->withCompetitors([$firstMatchWrestler])
        ->create();
    EventMatch::factory()
        ->forEvent($earliestEvent)
        ->withMatchNumber(1)
        ->withCompetitors([$earliestMatchWrestler])
        ->create();

    // Act
    $component = livewire(Main::class);

    // Assert
    $component->assertSeeInOrder([
        'First Match Wrestler',
        'Second Match Wrestler',
        'Earlier Match Wrestler',
    ]);
});

it('renders event, match type, competitors, and an empty result', function (): void {
    // Arrange
    $event = Event::factory()->create(['name' => 'Summer Spectacular']);
    $wrestler = Wrestler::factory()->create(['name' => 'Singles Wrestler']);
    $tagTeam = TagTeam::factory()->create(['name' => 'Tag Team']);
    EventMatch::factory()
        ->forEvent($event)
        ->withMatchNumber(3)
        ->withMatchType(MatchType::TagTeam)
        ->withCompetitors([$wrestler, $tagTeam])
        ->create();

    // Act
    $component = livewire(Main::class);

    // Assert
    $component
        ->assertSuccessful()
        ->assertSee('Summer Spectacular')
        ->assertSee(route('events.show', $event))
        ->assertSee('3')
        ->assertSee(MatchType::TagTeam->label())
        ->assertSee('Singles Wrestler')
        ->assertSee('Tag Team')
        ->assertSee('N/A');
});

it('searches matches by type and clears the search', function (): void {
    // Arrange
    EventMatch::factory()
        ->withMatchType(MatchType::Singles)
        ->create();
    EventMatch::factory()
        ->withMatchType(MatchType::TagTeam)
        ->create();
    $component = livewire(Main::class);

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

it('deletes a match', function (): void {
    // Arrange
    $match = EventMatch::factory()->create();

    // Act
    $component = livewire(Main::class);
    $component->call('delete', $match);

    // Assert
    $component
        ->assertHasNoErrors()
        ->assertDispatched(
            'flash-message',
            type: 'status',
            message: __('matches.actions.deleted'),
        );
    $this->assertSoftDeleted($match);
});

it('forbids users without match access', function (string $actor): void {
    // Arrange
    if ($actor === 'guest') {
        Auth::logout();
    } else {
        actingAs(basicUser());
    }

    // Act
    $component = livewire(Main::class);

    // Assert
    $component->assertForbidden();
})->with([
    'guest' => ['guest'],
    'basic user' => ['basic user'],
]);
