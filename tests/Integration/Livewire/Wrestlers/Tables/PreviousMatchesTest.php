<?php

declare(strict_types=1);

use App\Livewire\Wrestlers\Tables\PreviousMatches;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->wrestler = Wrestler::factory()->create();
    actingAs(administrator());
});

describe('PreviousMatches configuration', function (): void {
    it('requires a wrestler', function (): void {
        // Act & Assert
        expect(fn () => (new PreviousMatches())->builder())
            ->toThrow(LogicException::class, 'A wrestler was not provided.');
    });
});

describe('PreviousMatches query', function (): void {
    it('returns only past matches for the requested wrestler in newest-first order', function (): void {
        // Arrange
        $olderMatch = EventMatch::factory()
            ->for(Event::factory()->create(['date' => Date::now()->subYears(2)]))
            ->withCompetitors([$this->wrestler, Wrestler::factory()->create()])
            ->create();
        $recentMatch = EventMatch::factory()
            ->for(Event::factory()->create(['date' => Date::now()->subYear()]))
            ->withCompetitors([$this->wrestler, Wrestler::factory()->create()])
            ->create();
        EventMatch::factory()
            ->for(Event::factory()->future())
            ->withCompetitors([$this->wrestler, Wrestler::factory()->create()])
            ->create();
        EventMatch::factory()
            ->for(Event::factory()->past())
            ->withCompetitors(Wrestler::factory()->count(2)->create()->all())
            ->create();
        $table = new PreviousMatches();
        $table->wrestlerId = $this->wrestler->id;

        // Act
        $matches = $table->builder()->get();

        // Assert
        expect($matches->modelKeys())->toBe([
            $recentMatch->id,
            $olderMatch->id,
        ])->and($matches->every->relationLoaded('event'))->toBeTrue()
            ->and($matches->every->relationLoaded('competitors'))->toBeTrue();
    });
});

describe('PreviousMatches rendering', function (): void {
    it('renders wrestler match history with event details, links, and search control', function (): void {
        // Arrange
        $event = Event::factory()->create([
            'name' => 'Historic Wrestler Event',
            'date' => Date::parse('2024-01-15'),
        ]);
        EventMatch::factory()
            ->forEvent($event)
            ->withCompetitors([$this->wrestler, Wrestler::factory()->create()])
            ->create();

        // Act
        $table = livewire(PreviousMatches::class, ['wrestlerId' => $this->wrestler->id]);

        // Assert
        $table
            ->assertSuccessful()
            ->assertSeeHtml('placeholder="Search matches"')
            ->assertSee('Historic Wrestler Event')
            ->assertSee('2024-01-15')
            ->assertSeeHtml(route('events.show', $event))
            ->assertSeeHtml(route('wrestlers.show', $this->wrestler));
    });

    it('searches previous matches by event name', function (): void {
        // Arrange
        foreach (['Historic Wrestler Event', 'Former Wrestler Event'] as $name) {
            EventMatch::factory()
                ->for(Event::factory()->create([
                    'name' => $name,
                    'date' => Date::now()->subMonth(),
                ]))
                ->withCompetitors([$this->wrestler, Wrestler::factory()->create()])
                ->create();
        }

        // Act
        $table = livewire(PreviousMatches::class, ['wrestlerId' => $this->wrestler->id]);
        $table->set('search', 'Historic');

        // Assert
        $table
            ->assertSee('Historic Wrestler Event')
            ->assertDontSee('Former Wrestler Event');
    });

    it('renders an empty state when the wrestler has no previous matches', function (): void {
        // Act
        $table = livewire(PreviousMatches::class, ['wrestlerId' => $this->wrestler->id]);

        // Assert
        $table
            ->assertSuccessful()
            ->assertSee('No records found.');
    });
});

describe('PreviousMatches authorization', function (): void {
    it('allows administrators to view wrestler match history', function (): void {
        // Act
        $table = livewire(PreviousMatches::class, ['wrestlerId' => $this->wrestler->id]);

        // Assert
        $table->assertSuccessful();
    });

    it('forbids users without access to the wrestler', function (string $actor): void {
        // Arrange
        if ($actor === 'guest') {
            Auth::logout();
        } else {
            actingAs(basicUser());
        }

        // Act
        $table = livewire(PreviousMatches::class, ['wrestlerId' => $this->wrestler->id]);

        // Assert
        $table->assertForbidden();
    })->with([
        'guest' => ['guest'],
        'basic user' => ['basic user'],
    ]);
});
