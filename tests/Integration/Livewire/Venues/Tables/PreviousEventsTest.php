<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Livewire\Venues\Tables\PreviousEvents;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Users\User;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

describe('PreviousEvents query', function (): void {
    it('returns only the selected venue events in reverse chronological order with unscheduled events last', function (): void {
        $venue = Venue::factory()->create();

        // Arrange
        $recentEvent = Event::factory()->atVenue($venue)->create([
            'date' => Date::parse('2026-08-15'),
        ]);
        $olderEvent = Event::factory()->atVenue($venue)->create([
            'date' => Date::parse('2026-07-15'),
        ]);
        $futureEvent = Event::factory()->atVenue($venue)->create([
            'date' => Date::parse('2026-09-15'),
        ]);
        $unscheduledEvent = Event::factory()->atVenue($venue)->unscheduled()->create();
        Event::factory()->atVenue(Venue::factory()->create())->create([
            'date' => Date::parse('2026-08-01'),
        ]);
        $table = new PreviousEvents;
        $table->venueId = $venue->id;

        // Act
        $events = $table->builder()->get();

        // Assert
        expect($events->modelKeys())->toBe([
            $futureEvent->id,
            $recentEvent->id,
            $olderEvent->id,
            $unscheduledEvent->id,
        ]);
    });
});

describe('PreviousEvents rendering', function (): void {
    it('renders event links, formatted dates, and search controls', function (): void {
        $venue = Venue::factory()->create();

        // Arrange
        $event = Event::factory()->atVenue($venue)->create([
            'name' => 'Linked Wrestling Event',
            'date' => Date::parse('2026-08-15 19:00:00'),
        ]);

        // Act
        $table = livewire(PreviousEvents::class, ['venueId' => $venue->id]);

        // Assert
        $table
            ->assertSuccessful()
            ->assertSeeHtml('placeholder="Search events"')
            ->assertSee('Linked Wrestling Event')
            ->assertSeeHtml(route('events.show', $event))
            ->assertSee('2026-08-15');
    });

    it('searches the selected venue event history', function (): void {
        $venue = Venue::factory()->create();

        // Arrange
        Event::factory()->atVenue($venue)->create([
            'name' => 'Summer Spectacular',
        ]);
        Event::factory()->atVenue($venue)->create([
            'name' => 'Winter Warfare',
        ]);
        Event::factory()->atVenue(Venue::factory()->create())->create([
            'name' => 'Summer Elsewhere',
        ]);
        Event::factory()->atVenue($venue)->trashed()->create([
            'name' => 'Summer Deleted',
        ]);

        // Act
        $table = livewire(PreviousEvents::class, ['venueId' => $venue->id]);
        $table->set('search', 'Summer');

        // Assert
        $table
            ->assertSee('Summer Spectacular')
            ->assertDontSee('Winter Warfare')
            ->assertDontSee('Summer Elsewhere')
            ->assertDontSee('Summer Deleted');

        // Act
        $table->set('search', 'Elsewhere');

        // Assert
        $table
            ->assertSee('No matching records')
            ->assertSee('Clear search')
            ->assertDontSee('Summer Elsewhere');

        // Act
        $table->set('search', '');

        // Assert
        $table
            ->assertSee('Summer Spectacular')
            ->assertSee('Winter Warfare')
            ->assertDontSee('Summer Elsewhere')
            ->assertDontSee('Summer Deleted');
    });
});

describe('venue event history', function (): void {
    it('lists only the events of the active promotion for an administrator with a membership', function (): void {
        // Arrange
        $world = createIsolatedPromotions();
        $admin = User::factory()->administrator()->create(['status' => UserStatus::Active]);
        $world['attacker']->users()->attach($admin, ['role' => MembershipRole::Owner, 'status' => MembershipStatus::Active]);
        $venue = Venue::factory()->create();
        Event::factory()->for($world['attacker'], 'promotion')->scheduled()->create(['name' => 'AttackerEvent', 'venue_id' => $venue->id]);
        Event::factory()->for($world['victim'], 'promotion')->scheduled()->create(['name' => 'VictimEventAtVenue', 'venue_id' => $venue->id]);
        actingAs($admin);
        $page = get(route('venues.show', $venue))->assertOk();

        // Act
        $response = postLivewireUpdate(
            snapshotOf((string) $page->getContent(), 'venues.tables.previous-events'),
            [['method' => '$refresh']],
        );

        // Assert
        $response->assertOk();
        expect($response->json('components.0.effects.html'))
            ->toContain('AttackerEvent')
            ->not->toContain('VictimEventAtVenue');
    });
});
