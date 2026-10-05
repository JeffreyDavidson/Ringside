<?php

declare(strict_types=1);

use App\Enums\EventStatus;
use App\Livewire\Events\Tables\Main;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Promotions\Promotion;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

describe('events table', function (): void {
    it('renders event scheduling details and excludes deleted events', function (): void {
        // Arrange
        $venue = Venue::factory()->create(['name' => 'Madison Square Garden']);
        $scheduledDate = Date::tomorrow()->hour(19);
        Event::factory()->atVenue($venue)->create([
            'name' => 'Future Showcase',
            'date' => $scheduledDate,
        ]);
        Event::factory()->past()->atVenue($venue)->create(['name' => 'Past Showcase']);
        Event::factory()->unscheduled()->create(['name' => 'Draft Showcase']);
        $deletedEvent = Event::factory()->unscheduled()->create(['name' => 'Deleted Showcase']);
        $deletedEvent->delete();

        // Act
        $component = livewire(Main::class);

        // Assert
        $component
            ->assertSuccessful()
            ->assertSee('Add Event')
            ->assertSeeHtml('placeholder="Search events"')
            ->assertSeeHtml('data-test="events-table"')
            ->assertSeeHtml('data-test="events-status-filters"')
            ->assertSeeHtml('data-test="events-venue-filter"')
            ->assertSeeHtml('wire:model.live="filterValues.event_dates.minDate"')
            ->assertSeeHtml('wire:model.live="filterValues.event_dates.maxDate"')
            ->assertSeeHtml('data-test="table-toolbar"')
            ->assertSeeHtml('aria-label="Actions for Future Showcase"')
            ->assertSeeHtml('wire:confirm="Remove Future Showcase?"')
            ->assertSeeHtml('role="group"')
            ->assertSee('Future Showcase')
            ->assertSee('Past Showcase')
            ->assertSee('Draft Showcase')
            ->assertSee($scheduledDate->format('M j, Y'))
            ->assertSee(__('events.no_date'))
            ->assertSee($venue->name)
            ->assertSee(__('events.no_venue'))
            ->assertDontSee('Deleted Showcase');
    });

    it('renders the shared empty state when there are no events', function (): void {
        livewire(Main::class)
            ->assertSeeHtml('data-test="events-empty-state"')
            ->assertSee(__('events.empty_title'))
            ->assertSee(__('events.empty_description'))
            ->assertDontSee('No records found.');
    });

    it('searches events by name and clears the search', function (): void {
        // Arrange
        Event::factory()->unscheduled()->create(['name' => 'Summer Spectacular']);
        Event::factory()->unscheduled()->create(['name' => 'Winter Warfare']);
        $component = livewire(Main::class);

        // Act
        $component->set('search', 'Summer');

        // Assert
        $component
            ->assertSee('Summer Spectacular')
            ->assertDontSee('Winter Warfare');

        // Act
        $component->set('search', '');

        // Assert
        $component
            ->assertSee('Summer Spectacular')
            ->assertSee('Winter Warfare');
    });

    it('filters events by scheduling status', function (
        EventStatus $status,
        string $visibleEvent,
        string $firstHiddenEvent,
        string $secondHiddenEvent,
    ): void {
        // Arrange
        Event::factory()->past()->create(['name' => 'Past Showcase']);
        Event::factory()->scheduled()->create(['name' => 'Scheduled Showcase']);
        Event::factory()->unscheduled()->create(['name' => 'Draft Showcase']);
        $component = livewire(Main::class);

        // Act
        $component->set('filterValues.status', $status->value);

        // Assert
        $component
            ->assertSee($visibleEvent)
            ->assertDontSee($firstHiddenEvent)
            ->assertDontSee($secondHiddenEvent);
    })->with([
        'past' => [EventStatus::Past, 'Past Showcase', 'Scheduled Showcase', 'Draft Showcase'],
        'scheduled' => [EventStatus::Scheduled, 'Scheduled Showcase', 'Past Showcase', 'Draft Showcase'],
        'unscheduled' => [EventStatus::Unscheduled, 'Draft Showcase', 'Past Showcase', 'Scheduled Showcase'],
    ]);

    it('filters events by venue', function (): void {
        // Arrange
        $selectedVenue = Venue::factory()->create(['name' => 'Selected Arena']);
        $otherVenue = Venue::factory()->create(['name' => 'Other Arena']);
        Event::factory()->scheduled()->atVenue($selectedVenue)->create(['name' => 'Selected Venue Event']);
        Event::factory()->scheduled()->atVenue($otherVenue)->create(['name' => 'Other Venue Event']);
        Event::factory()->scheduled()->create(['name' => 'No Venue Event']);
        $component = livewire(Main::class);

        // Act
        $component->set('filterValues.venue', (string) $selectedVenue->id);

        // Assert
        $component
            ->assertSee('Selected Venue Event')
            ->assertDontSee('Other Venue Event')
            ->assertDontSee('No Venue Event');
    });

    it('lists only the venues this promotion\'s events use in the venue filter', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $otherPromotion = Promotion::factory()->create();
        $sharedVenue = Venue::factory()->create(['name' => 'Shared Arena']);
        $ownVenue = Venue::factory()->create(['name' => 'Own Arena']);
        $otherPromotionVenue = Venue::factory()->create(['name' => 'Other Promotion Arena']);
        Venue::factory()->create(['name' => 'Unused Arena']);
        $deletedEventVenue = Venue::factory()->create(['name' => 'Deleted Event Arena']);
        Event::factory()->for($promotion, 'promotion')->atVenue($sharedVenue)->create();
        Event::factory()->for($promotion, 'promotion')->atVenue($sharedVenue)->create();
        Event::factory()->for($promotion, 'promotion')->atVenue($ownVenue)->create();
        Event::factory()->for($promotion, 'promotion')->create(['venue_id' => null]);
        Event::factory()->for($promotion, 'promotion')->atVenue($deletedEventVenue)->create()->delete();
        Event::factory()->for($otherPromotion, 'promotion')->atVenue($sharedVenue)->create();
        Event::factory()->for($otherPromotion, 'promotion')->atVenue($otherPromotionVenue)->create();
        $context = app(PromotionContextService::class);
        $context->set($promotion);
        $context->enforce();

        // Act
        $component = livewire(Main::class);

        // Assert
        expect($component->instance()->getVenues())->toBe([
            $ownVenue->id => 'Own Arena',
            $sharedVenue->id => 'Shared Arena',
        ]);
        $component
            ->assertSee('Own Arena')
            ->assertSee('Shared Arena')
            ->assertDontSee('Other Promotion Arena')
            ->assertDontSee('Unused Arena')
            ->assertDontSee('Deleted Event Arena');

        $context->clear();
    });

    it('filters events within an inclusive date range', function (): void {
        // Arrange
        Event::factory()->scheduledOn('2026-05-31 23:59:59')->create(['name' => 'Before Range']);
        Event::factory()->scheduledOn('2026-06-01 00:00:00')->create(['name' => 'Range Start']);
        Event::factory()->scheduledOn('2026-06-15 19:00:00')->create(['name' => 'Within Range']);
        Event::factory()->scheduledOn('2026-06-30 23:59:59')->create(['name' => 'Range End']);
        Event::factory()->scheduledOn('2026-07-01 00:00:00')->create(['name' => 'After Range']);
        $component = livewire(Main::class);

        // Act
        $component->set('filterValues.event_dates', [
            'minDate' => '2026-06-01',
            'maxDate' => '2026-06-30',
        ]);

        // Assert
        $component
            ->assertDontSee('Before Range')
            ->assertSee('Range Start')
            ->assertSee('Within Range')
            ->assertSee('Range End')
            ->assertDontSee('After Range');
    });

    it('reads the chosen date range as days in the promotion time zone', function (
        string $timezone,
        array $included,
        array $excluded,
    ): void {
        // Arrange
        $promotion = Promotion::factory()->create(['timezone' => $timezone]);
        $context = app(PromotionContextService::class);
        $context->set($promotion);
        $context->enforce();

        foreach ([...$included, ...$excluded] as $name => $utcDate) {
            Event::factory()->scheduledOn($utcDate)->for($promotion, 'promotion')->create(['name' => $name]);
        }

        $component = livewire(Main::class);

        // Act
        $component->set('filterValues.event_dates', [
            'minDate' => '2026-06-10',
            'maxDate' => '2026-06-10',
        ]);

        // Assert
        foreach (array_keys($included) as $name) {
            $component->assertSee($name);
        }

        foreach (array_keys($excluded) as $name) {
            $component->assertDontSee($name);
        }

        $context->clear();
    })->with([
        'west of UTC' => ['America/New_York', [
            'Local Start' => '2026-06-10 04:00:00',
            'Local Evening' => '2026-06-11 03:59:59',
        ], [
            'Local Previous Evening' => '2026-06-10 03:59:59',
            'Local Next Day' => '2026-06-11 04:00:00',
        ]],
        'east of UTC' => ['Asia/Tokyo', [
            'Local Start' => '2026-06-09 15:00:00',
            'Local Evening' => '2026-06-10 14:59:59',
        ], [
            'Local Previous Evening' => '2026-06-09 14:59:59',
            'Local Next Day' => '2026-06-10 15:00:00',
        ]],
    ]);

    it('reads the chosen date range in the application time zone without a promotion context', function (): void {
        // Arrange
        config()->set('app.timezone', 'America/New_York');
        Event::factory()->scheduledOn('2026-06-10 03:59:59')->create(['name' => 'Previous Evening']);
        Event::factory()->scheduledOn('2026-06-10 04:00:00')->create(['name' => 'Local Start']);
        Event::factory()->scheduledOn('2026-06-11 03:59:59')->create(['name' => 'Local Evening']);
        Event::factory()->scheduledOn('2026-06-11 04:00:00')->create(['name' => 'Next Day']);
        $component = livewire(Main::class);

        // Act
        $component->set('filterValues.event_dates', [
            'minDate' => '2026-06-10',
            'maxDate' => '2026-06-10',
        ]);

        // Assert
        $component
            ->assertDontSee('Previous Evening')
            ->assertSee('Local Start')
            ->assertSee('Local Evening')
            ->assertDontSee('Next Day');
    });

    it('ignores malformed event date range values', function (array $dateRange): void {
        // Arrange
        Event::factory()->scheduledOn('2026-05-31 12:00:00')->create(['name' => 'Before Range']);
        Event::factory()->scheduledOn('2026-06-15 19:00:00')->create(['name' => 'Within Range']);
        $component = livewire(Main::class);

        // Act
        $component->set('filterValues.event_dates', $dateRange);

        // Assert
        $component
            ->assertOk()
            ->assertSee('Before Range')
            ->assertSee('Within Range');
    })->with([
        'malformed minimum' => [['minDate' => 'not-a-date', 'maxDate' => '2026-06-30']],
        'malformed maximum' => [['minDate' => '2026-06-01', 'maxDate' => 'not-a-date']],
        'both malformed' => [['minDate' => 'not-a-date', 'maxDate' => 'also-not-a-date']],
    ]);

    it('clears all event filters together', function (): void {
        // Arrange
        $venue = Venue::factory()->create(['name' => 'Clear Filter Arena']);
        Event::factory()->scheduled()->atVenue($venue)->create(['name' => 'Visible After Clear']);
        $component = livewire(Main::class)
            ->set('search', 'No matching event')
            ->set('filterValues.status', EventStatus::Past->value)
            ->set('filterValues.venue', (string) $venue->id)
            ->set('filterValues.event_dates', [
                'minDate' => '2026-06-01',
                'maxDate' => '2026-06-30',
            ]);

        // Act
        $component->call('clearFilters');

        // Assert
        $component
            ->assertSet('search', '')
            ->assertSet('filterValues.status', '')
            ->assertSet('filterValues.venue', '')
            ->assertSet('filterValues.event_dates', [])
            ->assertSee('Visible After Clear');
    });

    it('orders dated events newest first and unscheduled events last', function (): void {
        // Arrange
        Event::factory()->create([
            'name' => 'Later Event',
            'date' => Date::now()->addDays(3),
        ]);
        Event::factory()->create([
            'name' => 'Earlier Event',
            'date' => Date::tomorrow(),
        ]);
        Event::factory()->unscheduled()->create(['name' => 'Unscheduled Event']);

        // Act
        $component = livewire(Main::class);

        // Assert
        $component->assertSeeInOrder([
            'Later Event',
            'Earlier Event',
            'Unscheduled Event',
        ]);
    });

    it('soft deletes an event and reports success', function (): void {
        // Arrange
        $event = Event::factory()->unscheduled()->create();
        $component = livewire(Main::class);

        // Act
        $component->call('delete', $event);

        // Assert
        $component
            ->assertHasNoErrors()
            ->assertDispatched(
                'flash-message',
                type: 'status',
                message: __('events.actions.deleted'),
            );
        $this->assertSoftDeleted($event);
    });

    it('renders an empty state when there are no events', function (): void {
        // Act
        $component = livewire(Main::class);

        // Assert
        $component
            ->assertSuccessful()
            ->assertSee(__('events.empty_title'))
            ->assertSee(__('events.empty_description'));
    });

    it('forbids users without administrative access', function (string $actor): void {
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
});
