<?php

declare(strict_types=1);

use App\Livewire\Referees\Tables\PreviousMatches;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Referees\Referee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->referee = Referee::factory()->create();
    actingAs(administrator());
});

it('requires a referee', function (): void {
    // Act & Assert
    expect(fn () => (new PreviousMatches())->builder())
        ->toThrow(LogicException::class, 'A referee was not provided.');
});

it('returns past matches officiated by the referee', function (): void {
    // Arrange
    $pastMatch = EventMatch::factory()
        ->for(Event::factory()->past())
        ->create();
    $pastMatch->referees()->attach($this->referee);

    // Act
    $matches = tap(app(PreviousMatches::class), function (PreviousMatches $table): void {
        $table->refereeId = $this->referee->id;
    })->builder()->get();

    // Assert
    expect($matches->modelKeys())->toBe([$pastMatch->id]);
});

it('excludes past matches officiated by another referee', function (): void {
    // Arrange
    $otherMatch = EventMatch::factory()
        ->for(Event::factory()->past())
        ->create();
    $otherMatch->referees()->attach(Referee::factory()->create());

    // Act
    $matches = tap(app(PreviousMatches::class), function (PreviousMatches $table): void {
        $table->refereeId = $this->referee->id;
    })->builder()->get();

    // Assert
    expect($matches->modelKeys())->not->toContain($otherMatch->id);
});

it('excludes future matches officiated by the referee', function (): void {
    // Arrange
    $futureMatch = EventMatch::factory()
        ->for(Event::factory()->future())
        ->create();
    $futureMatch->referees()->attach($this->referee);

    // Act
    $matches = tap(app(PreviousMatches::class), function (PreviousMatches $table): void {
        $table->refereeId = $this->referee->id;
    })->builder()->get();

    // Assert
    expect($matches->modelKeys())->not->toContain($futureMatch->id);
});

it('renders referee match history for administrators', function (): void {
    // Arrange
    $event = Event::factory()->create([
        'name' => 'Historic Referee Event',
        'date' => Date::parse('2024-01-15'),
    ]);
    $match = EventMatch::factory()
        ->forEvent($event)
        ->create();
    $match->referees()->attach($this->referee);

    // Act
    $table = livewire(PreviousMatches::class, ['refereeId' => $this->referee->id]);

    // Assert
    $table
        ->assertSuccessful()
        ->assertSeeHtml('placeholder="Search matches"')
        ->assertSee('Historic Referee Event')
        ->assertSee('2024-01-15')
        ->assertSeeHtml(route('events.show', $event))
        ->assertSeeHtml(route('referees.show', $this->referee));
});

it('renders when the referee has no previous matches', function (): void {
    // Act
    $table = livewire(PreviousMatches::class, ['refereeId' => $this->referee->id]);

    // Assert
    $table
        ->assertSuccessful()
        ->assertSee('No records found.');
});

it('forbids users without access to the referee', function (string $actor): void {
    // Arrange
    if ($actor === 'guest') {
        Auth::logout();
    } else {
        actingAs(basicUser());
    }

    // Act
    $table = livewire(PreviousMatches::class, ['refereeId' => $this->referee->id]);

    // Assert
    $table->assertForbidden();
})->with([
    'guest' => ['guest'],
    'basic user' => ['basic user'],
]);
