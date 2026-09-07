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

describe('PreviousMatches configuration', function (): void {
    it('requires a referee', function (): void {
        // Act & Assert
        expect(fn () => (new PreviousMatches())->builder())
            ->toThrow(LogicException::class, 'A referee was not provided.');
    });
});

describe('PreviousMatches query', function (): void {
    it('returns only past matches for the requested referee in newest-first order', function (): void {
        // Arrange
        $otherReferee = Referee::factory()->create();
        $olderMatch = EventMatch::factory()
            ->for(Event::factory()->create(['date' => Date::now()->subYears(2)]))
            ->create();
        $olderMatch->referees()->attach($this->referee);
        $recentMatch = EventMatch::factory()
            ->for(Event::factory()->create(['date' => Date::now()->subYear()]))
            ->create();
        $recentMatch->referees()->attach($this->referee);
        $futureMatch = EventMatch::factory()
            ->for(Event::factory()->future())
            ->create();
        $futureMatch->referees()->attach($this->referee);
        $otherMatch = EventMatch::factory()
            ->for(Event::factory()->past())
            ->create();
        $otherMatch->referees()->attach($otherReferee);
        $table = new PreviousMatches();
        $table->refereeId = $this->referee->id;

        // Act
        $matches = $table->builder()->get();

        // Assert
        expect($matches->modelKeys())->toBe([
            $recentMatch->id,
            $olderMatch->id,
        ])->and($matches->every->relationLoaded('event'))->toBeTrue()
            ->and($matches->every->relationLoaded('referees'))->toBeTrue();
    });
});

describe('PreviousMatches rendering', function (): void {
    it('renders referee match history with event details, links, and search control', function (): void {
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

    it('searches previous matches by event name', function (): void {
        // Arrange
        foreach (['Historic Referee Event', 'Former Referee Event'] as $name) {
            $match = EventMatch::factory()
                ->for(Event::factory()->create([
                    'name' => $name,
                    'date' => Date::now()->subMonth(),
                ]))
                ->create();
            $match->referees()->attach($this->referee);
        }

        EventMatch::factory()
            ->forEvent(Event::factory()->create([
                'name' => 'Historic Unrelated Event',
                'date' => Date::now()->subMonth(),
            ]))
            ->create();
        $deletedMatch = EventMatch::factory()
            ->forEvent(Event::factory()->create([
                'name' => 'Historic Deleted Event',
                'date' => Date::now()->subMonth(),
            ]))
            ->trashed()
            ->create();
        $deletedMatch->referees()->attach($this->referee);

        // Act
        $table = livewire(PreviousMatches::class, ['refereeId' => $this->referee->id]);
        $table->set('search', '  Historic  ');

        // Assert
        $table
            ->assertSee('Historic Referee Event')
            ->assertDontSee('Former Referee Event')
            ->assertDontSee('Historic Unrelated Event')
            ->assertDontSee('Historic Deleted Event');
    });

    it('renders an empty state when the referee has no previous matches', function (): void {
        // Act
        $table = livewire(PreviousMatches::class, ['refereeId' => $this->referee->id]);

        // Assert
        $table
            ->assertSuccessful()
            ->assertSee('No records found.');
    });
});

describe('PreviousMatches authorization', function (): void {
    it('allows administrators to view referee match history', function (): void {
        // Act
        $table = livewire(PreviousMatches::class, ['refereeId' => $this->referee->id]);

        // Assert
        $table->assertSuccessful();
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
});
