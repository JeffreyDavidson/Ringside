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

describe('PreviousMatches configuration', function (): void {
    it('requires a tag team', function (): void {
        // Act & Assert
        expect(fn () => (new PreviousMatches())->builder())
            ->toThrow(LogicException::class, 'A tag team was not provided.');
    });
});

describe('PreviousMatches query', function (): void {
    it('returns only past matches for the requested tag team in newest-first order', function (): void {
        // Arrange
        $olderMatch = EventMatch::factory()
            ->for(Event::factory()->create(['date' => Date::now()->subYears(2)]))
            ->withCompetitors([$this->tagTeam, TagTeam::factory()->create()])
            ->create();
        $recentMatch = EventMatch::factory()
            ->for(Event::factory()->create(['date' => Date::now()->subYear()]))
            ->withCompetitors([$this->tagTeam, TagTeam::factory()->create()])
            ->create();
        EventMatch::factory()
            ->for(Event::factory()->future())
            ->withCompetitors([$this->tagTeam, TagTeam::factory()->create()])
            ->create();
        EventMatch::factory()
            ->for(Event::factory()->past())
            ->withCompetitors(TagTeam::factory()->count(2)->create()->all())
            ->create();
        $table = new PreviousMatches();
        $table->tagTeamId = $this->tagTeam->id;

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
    it('renders tag team match history with event details, links, and search control', function (): void {
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

    it('searches previous matches by event name', function (): void {
        // Arrange
        foreach (['Historic Tag Team Event', 'Former Tag Team Event'] as $name) {
            EventMatch::factory()
                ->for(Event::factory()->create([
                    'name' => $name,
                    'date' => Date::now()->subMonth(),
                ]))
                ->withCompetitors([$this->tagTeam, TagTeam::factory()->create()])
                ->create();
        }

        // Act
        $table = livewire(PreviousMatches::class, ['tagTeamId' => $this->tagTeam->id]);
        $table->set('search', 'Historic');

        // Assert
        $table
            ->assertSee('Historic Tag Team Event')
            ->assertDontSee('Former Tag Team Event');
    });

    it('renders an empty state when the tag team has no previous matches', function (): void {
        // Act
        $table = livewire(PreviousMatches::class, ['tagTeamId' => $this->tagTeam->id]);

        // Assert
        $table
            ->assertSuccessful()
            ->assertSee('No records found.');
    });
});

describe('PreviousMatches authorization', function (): void {
    it('allows administrators to view tag team match history', function (): void {
        // Act
        $table = livewire(PreviousMatches::class, ['tagTeamId' => $this->tagTeam->id]);

        // Assert
        $table->assertSuccessful();
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
});
