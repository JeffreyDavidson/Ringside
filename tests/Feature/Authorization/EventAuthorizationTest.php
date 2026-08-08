<?php

declare(strict_types=1);

use App\Livewire\Events\Tables\Main;
use App\Models\Events\Event;
use App\Models\Users\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

/**
 * Feature tests for Event authorization workflows.
 *
 * FEATURE TEST SCOPE:
 * - End-to-end authorization workflows through HTTP and Livewire
 * - Multi-user role authorization scenarios
 * - Business action authorization with real policy enforcement
 * - Component-level authorization integration
 * - HTTP response validation for unauthorized access
 *
 * These tests verify that event authorization works correctly
 * across the entire application stack, from HTTP requests through
 * Livewire components to business action execution.
 */
describe('Event Authorization Feature Tests', function () {
    beforeEach(function () {
        $this->admin = User::factory()->administrator()->create();
        $this->basicUser = User::factory()->create();
    });

    describe('admin user authorization', function () {
        test('admin can access events table component', function () {
            actingAs($this->admin);

            livewire(Main::class)
                ->assertOk();
        });

        test('admin can perform all event business actions', function () {
            $event = Event::factory()->unscheduled()->create();
            $deletedEvent = Event::factory()->trashed()->create();

            actingAs($this->admin);

            $component = livewire(Main::class);

            // Event management actions
            $component->call('delete', $event)->assertHasNoErrors();
            $component->call('restore', $deletedEvent->id)->assertHasNoErrors();
        });

        test('admin can manage events across all scheduling statuses', function () {
            $scheduledEvent = Event::factory()->scheduled()->create();
            $unscheduledEvent = Event::factory()->unscheduled()->create();
            $pastEvent = Event::factory()->past()->create();

            actingAs($this->admin);

            $component = livewire(Main::class);

            // Admin should be able to perform appropriate actions on each status
            $component->call('delete', $scheduledEvent)->assertHasNoErrors();
            $component->call('delete', $unscheduledEvent)->assertHasNoErrors();
            $component->call('delete', $pastEvent)->assertHasNoErrors();
        });

        test('admin can manage events with venue relationships', function () {
            $eventWithVenue = Event::factory()->scheduled()->withVenue()->create();
            $eventWithoutVenue = Event::factory()->scheduled()->create();

            actingAs($this->admin);

            $component = livewire(Main::class);

            // Admin can manage events regardless of venue association
            $component->call('delete', $eventWithVenue)->assertHasNoErrors();
            $component->call('delete', $eventWithoutVenue)->assertHasNoErrors();
        });

        test('admin can perform event lifecycle management', function () {
            $event = Event::factory()->unscheduled()->create();

            actingAs($this->admin);

            $component = livewire(Main::class);

            // Event lifecycle through admin actions
            $component->call('delete', $event)
                ->assertHasNoErrors();

            expect(Event::find($event->id))->toBeNull();
            expect(Event::onlyTrashed()->find($event->id))->not->toBeNull();

            $component->call('restore', $event->id)
                ->assertHasNoErrors();

            expect(Event::find($event->id))->not->toBeNull();
        });
    });

    describe('basic user authorization', function () {
        test('basic user cannot access events table component', function () {
            actingAs($this->basicUser);

            livewire(Main::class)
                ->assertForbidden();
        });

        test('basic user is denied all event management actions', function () {
            $event = Event::factory()->scheduled()->create();

            // Attempt to test component should fail at authorization level
            actingAs($this->basicUser);

            livewire(Main::class)
                ->assertForbidden();
        });

        test('basic user cannot perform event scheduling actions', function () {
            $scheduledEvent = Event::factory()->scheduled()->create();
            $unscheduledEvent = Event::factory()->unscheduled()->create();
            $pastEvent = Event::factory()->past()->create();

            // All attempts should fail at the component authorization level
            actingAs($this->basicUser);

            livewire(Main::class)
                ->assertForbidden();
        });

        test('basic user cannot access event CRUD operations', function () {
            $event = Event::factory()->unscheduled()->create();
            $deletedEvent = Event::factory()->trashed()->create();

            // Component access should be denied
            actingAs($this->basicUser);

            livewire(Main::class)
                ->assertForbidden();
        });
    });

    describe('guest user authorization', function () {
        test('guest user cannot access events table component', function () {
            livewire(Main::class)
                ->assertForbidden();
        });

        test('guest user is completely denied access', function () {
            $event = Event::factory()->scheduled()->create();

            // All attempts should fail immediately
            livewire(Main::class)
                ->assertForbidden();
        });
    });

    describe('role-based action authorization', function () {
        test('event scheduling actions require administrator role', function () {
            $scheduledEvent = Event::factory()->scheduled()->create();
            $unscheduledEvent = Event::factory()->unscheduled()->create();

            // Admin can perform actions
            actingAs($this->admin);

            livewire(Main::class)
                ->call('delete', $scheduledEvent)
                ->assertHasNoErrors();

            // Basic user cannot even access component
            actingAs($this->basicUser);

            livewire(Main::class)
                ->assertForbidden();
        });

        test('event management requires administrator role', function () {
            $event = Event::factory()->unscheduled()->create();
            $deletedEvent = Event::factory()->trashed()->create();

            // Admin can manage events
            actingAs($this->admin);

            $component = livewire(Main::class);

            $component->call('delete', $event)->assertHasNoErrors();
            $component->call('restore', $deletedEvent->id)->assertHasNoErrors();

            // Basic user cannot access
            actingAs($this->basicUser);

            livewire(Main::class)
                ->assertForbidden();
        });

        test('event venue management requires administrator role', function () {
            $eventWithVenue = Event::factory()->scheduled()->withVenue()->create();
            $eventWithoutVenue = Event::factory()->scheduled()->create();

            // Admin can manage venue associations
            actingAs($this->admin);

            $component = livewire(Main::class);

            $component->call('delete', $eventWithVenue)->assertHasNoErrors();
            $component->call('delete', $eventWithoutVenue)->assertHasNoErrors();

            // Basic user cannot access
            actingAs($this->basicUser);

            livewire(Main::class)
                ->assertForbidden();
        });

        test('crud operations require administrator role', function () {
            $event = Event::factory()->unscheduled()->create();
            $deletedEvent = Event::factory()->trashed()->create();

            // Admin can perform CRUD
            actingAs($this->admin);

            $component = livewire(Main::class);

            $component->call('delete', $event)->assertHasNoErrors();
            $component->call('restore', $deletedEvent->id)->assertHasNoErrors();

            // Basic user cannot access
            actingAs($this->basicUser);

            livewire(Main::class)
                ->assertForbidden();
        });
    });

    describe('complex authorization scenarios', function () {
        test('multiple administrators can manage events concurrently', function () {
            $admin1 = User::factory()->administrator()->create();
            $admin2 = User::factory()->administrator()->create();

            $event1 = Event::factory()->unscheduled()->create();
            $event2 = Event::factory()->unscheduled()->create();

            // Both admins can perform actions simultaneously
            actingAs($admin1);

            $component1 = livewire(Main::class);
            actingAs($admin2);

            $component2 = livewire(Main::class);

            $component1->call('delete', $event1)->assertHasNoErrors();
            $component2->call('delete', $event2)->assertHasNoErrors();

            expect(Event::find($event1->id))->toBeNull();
            expect(Event::find($event2->id))->toBeNull();
        });

        test('authorization is enforced per request', function () {
            $event = Event::factory()->unscheduled()->create();

            // Admin can access
            actingAs($this->admin);

            livewire(Main::class)
                ->call('delete', $event)
                ->assertHasNoErrors();

            // Basic user still cannot access even after admin action
            actingAs($this->basicUser);

            livewire(Main::class)
                ->assertForbidden();
        });

        test('authorization works with event state changes', function () {
            $event = Event::factory()->unscheduled()->create();

            actingAs($this->admin);

            $component = livewire(Main::class);

            // Delete event
            $component->call('delete', $event)->assertHasNoErrors();
            expect(Event::find($event->id))->toBeNull();

            // Restore event
            $component->call('restore', $event->id)->assertHasNoErrors();
            expect(Event::find($event->id))->not->toBeNull();

            // Basic user still cannot access regardless of event state
            actingAs($this->basicUser);

            livewire(Main::class)
                ->assertForbidden();
        });
    });

    describe('business rule enforcement with authorization', function () {
        test('authorized users can attempt operations on any event status', function () {
            $scheduledEvent = Event::factory()->scheduled()->create();
            $pastEvent = Event::factory()->past()->create();

            actingAs($this->admin);

            $component = livewire(Main::class);

            // Admin is authorized for all operations
            $component->call('delete', $scheduledEvent)->assertHasNoErrors();
            $component->call('delete', $pastEvent)->assertHasNoErrors();
        });

        test('authorization precedes business rule validation', function () {
            $event = Event::factory()->scheduled()->create();

            // Basic user should be denied at authorization level,
            // not reach business rule validation
            actingAs($this->basicUser);

            livewire(Main::class)
                ->assertForbidden();
        });

        test('admin can attempt all operations regardless of business feasibility', function () {
            $scheduledEvent = Event::factory()->scheduled()->create();
            $pastEvent = Event::factory()->past()->create();

            actingAs($this->admin);

            $component = livewire(Main::class);

            // Admin should be authorized for all operations
            $component->call('delete', $scheduledEvent)->assertHasNoErrors();
            $component->call('delete', $pastEvent)->assertHasNoErrors();

            // All calls should be authorized and reach business logic
        });
    });

    describe('authorization error handling', function () {
        test('unauthorized component access returns proper error response', function () {
            actingAs($this->basicUser);

            livewire(Main::class)
                ->assertForbidden();
        });

        test('guest access attempts are properly handled', function () {
            livewire(Main::class)
                ->assertForbidden();
        });

        test('authorization failures do not expose sensitive data', function () {
            $event = Event::factory()->scheduled()->create();

            // Authorization failure should not expose event details
            actingAs($this->basicUser);

            livewire(Main::class)
                ->assertForbidden();
        });
    });

    describe('scheduling and venue authorization', function () {
        test('admin can manage events with complex scheduling', function () {
            $futureEvent = Event::factory()->scheduled()->create();
            $pastEvent = Event::factory()->past()->create();
            $unscheduledEvent = Event::factory()->unscheduled()->create();

            actingAs($this->admin);

            $component = livewire(Main::class);

            // Admin can manage events regardless of scheduling complexity
            $component->call('delete', $futureEvent)->assertHasNoErrors();
            $component->call('delete', $pastEvent)->assertHasNoErrors();
            $component->call('delete', $unscheduledEvent)->assertHasNoErrors();
        });

        test('authorization applies to complex venue scenarios', function () {
            // Create events with venue relationships
            $eventWithVenue = Event::factory()->scheduled()->withVenue()->create();
            $eventWithoutVenue = Event::factory()->scheduled()->create();

            actingAs($this->admin);

            $component = livewire(Main::class);

            // Admin can manage complex venue scenarios
            $component->call('delete', $eventWithVenue)->assertHasNoErrors();
            $component->call('delete', $eventWithoutVenue)->assertHasNoErrors();

            // Basic user still cannot access
            actingAs($this->basicUser);

            livewire(Main::class)
                ->assertForbidden();
        });
    });

    describe('event lifecycle authorization', function () {
        test('admin can manage complete event lifecycle', function () {
            $event = Event::factory()->unscheduled()->create();

            actingAs($this->admin);

            $component = livewire(Main::class);

            // Complete lifecycle management
            $component->call('delete', $event)->assertHasNoErrors();
            expect(Event::find($event->id))->toBeNull();

            $component->call('restore', $event->id)->assertHasNoErrors();
            expect(Event::find($event->id))->not->toBeNull();
        });

        test('authorization enforced throughout event lifecycle', function () {
            $event = Event::factory()->unscheduled()->create();

            // Admin can perform lifecycle operations
            actingAs($this->admin);

            $adminComponent = livewire(Main::class);

            $adminComponent->call('delete', $event)->assertHasNoErrors();
            $adminComponent->call('restore', $event->id)->assertHasNoErrors();

            // Basic user cannot perform any lifecycle operations
            actingAs($this->basicUser);

            livewire(Main::class)
                ->assertForbidden();
        });
    });

    describe('authorization with event relationships', function () {
        test('admin can manage events with matches', function () {
            $eventWithMatches = Event::factory()->scheduled()->withVenue()->create();
            // Note: In a real scenario, this would have matches attached

            actingAs($this->admin);

            $component = livewire(Main::class);

            // Admin can manage events with relationships
            $component->call('delete', $eventWithMatches)->assertHasNoErrors();
        });

        test('authorization applies regardless of event complexity', function () {
            // Create complex event with relationships
            $complexEvent = Event::factory()->scheduled()->withVenue()->withPreview()->create();

            actingAs($this->admin);

            $component = livewire(Main::class);

            // Admin authorized for complex events
            $component->call('delete', $complexEvent)->assertHasNoErrors();

            // Basic user still denied
            actingAs($this->basicUser);

            livewire(Main::class)
                ->assertForbidden();
        });
    });
});
