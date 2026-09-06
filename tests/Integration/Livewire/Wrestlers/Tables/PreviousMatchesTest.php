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

describe('PreviousMatchesTable Configuration', function () {
    it('requires wrestler id to be set', function (): void {
        // Act & Assert
        expect(fn () => (new PreviousMatches())->builder())
            ->toThrow(LogicException::class, 'A wrestler was not provided.');
    });

    it('can set wrestler id', function (): void {
        // Act
        $component = livewire(PreviousMatches::class, ['wrestlerId' => $this->wrestler->id]);

        // Assert
        $component->assertSet('wrestlerId', $this->wrestler->id);
    });

    it('renders the match history search control', function (): void {
        // Act
        $component = livewire(PreviousMatches::class, ['wrestlerId' => $this->wrestler->id]);

        // Assert
        $component
            ->assertSuccessful()
            ->assertSeeHtml('placeholder="Search matches"');
    });
});

describe('PreviousMatchesTable Query Building', function () {
    it('returns past matches featuring the wrestler', function (): void {
        // Arrange
        $pastEvent = Event::factory()->create(['date' => Date::parse('2024-01-15')]);
        $pastMatch = EventMatch::factory()
            ->forEvent($pastEvent)
            ->withCompetitors([$this->wrestler, Wrestler::factory()->create()])
            ->create();

        // Act
        $matches = tap(app(PreviousMatches::class), function (PreviousMatches $table): void {
            $table->wrestlerId = $this->wrestler->id;
        })->builder()->get();

        // Assert
        expect($matches->modelKeys())->toBe([$pastMatch->id]);
    });

    it('excludes past matches featuring another wrestler', function (): void {
        // Arrange
        $otherMatch = EventMatch::factory()
            ->for(Event::factory()->past())
            ->withCompetitors(Wrestler::factory()->count(2)->create()->all())
            ->create();

        // Act
        $matches = tap(app(PreviousMatches::class), function (PreviousMatches $table): void {
            $table->wrestlerId = $this->wrestler->id;
        })->builder()->get();

        // Assert
        expect($matches->modelKeys())->not->toContain($otherMatch->id);
    });

    it('excludes future matches featuring the wrestler', function (): void {
        // Arrange
        $futureMatch = EventMatch::factory()
            ->for(Event::factory()->future())
            ->withCompetitors([$this->wrestler, Wrestler::factory()->create()])
            ->create();

        // Act
        $matches = tap(app(PreviousMatches::class), function (PreviousMatches $table): void {
            $table->wrestlerId = $this->wrestler->id;
        })->builder()->get();

        // Assert
        expect($matches->modelKeys())->not->toContain($futureMatch->id);
    });
});

describe('PreviousMatchesTable Rendering', function () {
    it('renders a previous match event', function (): void {
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
        $component = livewire(PreviousMatches::class, ['wrestlerId' => $this->wrestler->id]);

        // Assert
        $component
            ->assertSuccessful()
            ->assertSee('Historic Wrestler Event')
            ->assertSee('2024-01-15')
            ->assertSeeHtml(route('events.show', $event))
            ->assertSeeHtml(route('wrestlers.show', $this->wrestler));
    });

    it('renders when the wrestler has no previous matches', function (): void {
        // Act
        $component = livewire(PreviousMatches::class, ['wrestlerId' => $this->wrestler->id]);

        // Assert
        $component
            ->assertSuccessful()
            ->assertSee('No records found.');
    });
});

describe('PreviousMatchesTable Authorization', function () {
    it('allows access to administrators', function (): void {
        // Act
        $component = livewire(PreviousMatches::class, ['wrestlerId' => $this->wrestler->id]);

        // Assert
        $component->assertSuccessful();
    });

    it('forbids users without access to the wrestler', function (string $actor): void {
        // Arrange
        if ($actor === 'guest') {
            Auth::logout();
        } else {
            actingAs(basicUser());
        }

        // Act
        $component = livewire(PreviousMatches::class, ['wrestlerId' => $this->wrestler->id]);

        // Assert
        $component->assertForbidden();
    })->with([
        'guest' => ['guest'],
        'basic user' => ['basic user'],
    ]);
});
