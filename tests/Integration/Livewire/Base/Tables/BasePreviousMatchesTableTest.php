<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

describe('previous matches tables', function (): void {
    it('renders match history through the shared table', function (
        string $component,
        string $ownerParameter,
        string $ownerRouteName,
        Closure $createOwner,
        Closure $bookMatch,
        string $participantRelation,
    ): void {
        // Arrange
        $owner = $createOwner();
        $event = Event::factory()->create([
            'name' => 'Historic Match Event',
            'date' => Date::parse('2025-01-15 20:00:00'),
        ]);
        $bookMatch(EventMatch::factory()->forEvent($event), $owner);

        // Act
        $table = livewire($component, [$ownerParameter => $owner->getKey()]);

        // Assert
        $table
            ->assertSuccessful()
            ->assertSeeHtml('placeholder="Search matches"')
            ->assertSee('Historic Match Event')
            ->assertSee('2025-01-15')
            ->assertSeeHtml(route('events.show', $event))
            ->assertSeeHtml(route($ownerRouteName, $owner));
    })->with('livewire previous matches tables');

    it('returns only past matches for the requested owner in newest-first order', function (
        string $component,
        string $ownerParameter,
        string $ownerRouteName,
        Closure $createOwner,
        Closure $bookMatch,
        string $participantRelation,
    ): void {
        // Arrange
        $owner = $createOwner();
        $olderMatch = $bookMatch(
            EventMatch::factory()->for(Event::factory()->create(['date' => Date::now()->subYears(2)])),
            $owner,
        );
        $recentMatch = $bookMatch(
            EventMatch::factory()->for(Event::factory()->create(['date' => Date::now()->subYear()])),
            $owner,
        );
        $bookMatch(EventMatch::factory()->for(Event::factory()->future()), $owner);
        $bookMatch(EventMatch::factory()->for(Event::factory()->past()), $createOwner());
        $table = app($component);
        $table->{$ownerParameter} = $owner->getKey();

        // Act
        $matches = $table->builder()->get();

        // Assert
        expect($matches->modelKeys())->toBe([
            $recentMatch->id,
            $olderMatch->id,
        ])->and($matches->every->relationLoaded('event'))->toBeTrue()
            ->and($matches->every->relationLoaded($participantRelation))->toBeTrue();
    })->with('livewire previous matches tables');

    it('searches previous matches by event name', function (
        string $component,
        string $ownerParameter,
        string $ownerRouteName,
        Closure $createOwner,
        Closure $bookMatch,
        string $participantRelation,
    ): void {
        // Arrange
        $owner = $createOwner();
        foreach (['Historic Owner Event', 'Former Owner Event'] as $name) {
            $bookMatch(
                EventMatch::factory()->for(Event::factory()->create([
                    'name' => $name,
                    'date' => Date::now()->subMonth(),
                ])),
                $owner,
            );
        }

        EventMatch::factory()
            ->forEvent(Event::factory()->create([
                'name' => 'Historic Unrelated Event',
                'date' => Date::now()->subMonth(),
            ]))
            ->create();
        $bookMatch(
            EventMatch::factory()
                ->forEvent(Event::factory()->create([
                    'name' => 'Historic Deleted Event',
                    'date' => Date::now()->subMonth(),
                ]))
                ->trashed(),
            $owner,
        );

        // Act
        $table = livewire($component, [$ownerParameter => $owner->getKey()]);
        $table->set('search', '  Historic  ');

        // Assert
        $table
            ->assertSee('Historic Owner Event')
            ->assertDontSee('Former Owner Event')
            ->assertDontSee('Historic Unrelated Event')
            ->assertDontSee('Historic Deleted Event');
    })->with('livewire previous matches tables');
});
