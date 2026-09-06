<?php

declare(strict_types=1);

use App\Livewire\TagTeams\Tables\PreviousMatches;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\TagTeams\TagTeam;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->tagTeam = TagTeam::factory()->create();
    actingAs(administrator());
});

it('requires a tag team', function (): void {
    // Act & Assert
    expect(fn () => (new PreviousMatches())->builder())
        ->toThrow(LogicException::class, 'A tag team was not provided.');
});

it('returns past matches featuring the tag team', function (): void {
    // Arrange
    $pastEvent = Event::factory()->create(['date' => Date::parse('2024-01-15')]);
    $pastMatch = EventMatch::factory()
        ->forEvent($pastEvent)
        ->withCompetitors([$this->tagTeam, TagTeam::factory()->create()])
        ->create();

    // Act
    $matches = tap(app(PreviousMatches::class), function (PreviousMatches $table): void {
        $table->tagTeamId = $this->tagTeam->id;
    })->builder()->get();

    // Assert
    expect($matches->modelKeys())->toBe([$pastMatch->id]);
});

it('excludes past matches featuring another tag team', function (): void {
    // Arrange
    $otherMatch = EventMatch::factory()
        ->for(Event::factory()->past())
        ->withCompetitors(TagTeam::factory()->count(2)->create()->all())
        ->create();

    // Act
    $matches = tap(app(PreviousMatches::class), function (PreviousMatches $table): void {
        $table->tagTeamId = $this->tagTeam->id;
    })->builder()->get();

    // Assert
    expect($matches->modelKeys())->not->toContain($otherMatch->id);
});

it('excludes future matches featuring the tag team', function (): void {
    // Arrange
    $futureMatch = EventMatch::factory()
        ->for(Event::factory()->future())
        ->withCompetitors([$this->tagTeam, TagTeam::factory()->create()])
        ->create();

    // Act
    $matches = tap(app(PreviousMatches::class), function (PreviousMatches $table): void {
        $table->tagTeamId = $this->tagTeam->id;
    })->builder()->get();

    // Assert
    expect($matches->modelKeys())->not->toContain($futureMatch->id);
});

it('renders tag team match history for administrators', function (): void {
    // Arrange
    $event = Event::factory()->create([
        'name' => 'Historic Tag Team Event',
        'date' => Date::parse('2024-01-15'),
    ]);
    EventMatch::factory()
        ->forEvent($event)
        ->withCompetitors([$this->tagTeam, TagTeam::factory()->create()])
        ->create();

    // Act
    $table = livewire(PreviousMatches::class, ['tagTeamId' => $this->tagTeam->id]);

    // Assert
    $table
        ->assertSuccessful()
        ->assertSeeHtml('placeholder="Search matches"')
        ->assertSee('Historic Tag Team Event')
        ->assertSee('2024-01-15')
        ->assertSeeHtml(route('events.show', $event))
        ->assertSeeHtml(route('tag-teams.show', $this->tagTeam));
});

it('renders when the tag team has no previous matches', function (): void {
    // Act
    $table = livewire(PreviousMatches::class, ['tagTeamId' => $this->tagTeam->id]);

    // Assert
    $table
        ->assertSuccessful()
        ->assertSee('No records found.');
});

it('forbids users without access to the tag team', function (string $actor): void {
    // Arrange
    if ($actor === 'guest') {
        Auth::logout();
    } else {
        actingAs(basicUser());
    }

    // Act
    $table = livewire(PreviousMatches::class, ['tagTeamId' => $this->tagTeam->id]);

    // Assert
    $table->assertForbidden();
})->with([
    'guest' => ['guest'],
    'basic user' => ['basic user'],
]);
