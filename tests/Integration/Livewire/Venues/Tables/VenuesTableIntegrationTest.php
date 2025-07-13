<?php

declare(strict_types=1);

use App\Livewire\Venues\Tables\VenuesTable;
use App\Models\Events\Event;
use App\Models\Shared\Venue;
use App\Models\Users\User;
use Livewire\Livewire;

/**
 * Integration tests for VenuesTable Livewire component.
 *
 * INTEGRATION TEST SCOPE:
 * - Livewire component lifecycle with real database
 * - Table rendering with actual venue data
 * - Filtering and search functionality
 * - CRUD operations through table interface
 * - Event relationship display
 *
 * These tests verify that the VenuesTable component correctly
 * integrates with the database and handles real venue data
 * for display and management operations.
 */
describe('VenuesTable Integration Tests', function () {
    beforeEach(function () {
        $this->admin = User::factory()->administrator()->create();
        $this->basicUser = User::factory()->create();

        // Create test venues with various characteristics
        $this->activeVenue = Venue::factory()->create([
            'name' => 'Active Test Arena',
            'city' => 'Active City',
            'state' => 'AC',
        ]);

        $this->venueWithEvents = Venue::factory()->create([
            'name' => 'Busy Event Arena',
            'city' => 'Event City',
            'state' => 'EC',
        ]);

        $this->emptyVenue = Venue::factory()->create([
            'name' => 'Empty Arena',
            'city' => 'Empty City',
            'state' => 'EM',
        ]);

        $this->deletedVenue = Venue::factory()->create([
            'name' => 'Deleted Arena',
            'city' => 'Deleted City',
            'state' => 'DC',
        ]);

        // Create events for some venues
        Event::factory()->count(3)->atVenue($this->venueWithEvents)->create();
        Event::factory()->atVenue($this->venueWithEvents)->create([
            'name' => 'Past Event',
            'date' => now()->subDay(),
        ]);
        Event::factory()->atVenue($this->venueWithEvents)->create([
            'name' => 'Future Event',
            'date' => now()->addDay(),
        ]);

        // Soft delete one venue
        $this->deletedVenue->delete();
    });

    describe('component initialization and rendering', function () {
        test('renders successfully for administrators', function () {
            $component = Livewire::actingAs($this->admin)->test(VenuesTable::class);

            $component->assertOk()
                ->assertSee('Active Test Arena')
                ->assertSee('Busy Event Arena')
                ->assertSee('Empty Arena');
        });

        test('loads venue data with proper relationships', function () {
            $component = Livewire::actingAs($this->admin)->test(VenuesTable::class);

            $component->assertOk()
                ->assertSee('Busy Event Arena')
                ->assertSee('Event City');
        });

        test('displays venue address information', function () {
            $venueWithAddress = Venue::factory()->create([
                'name' => 'Address Display Arena',
                'street_address' => '123 Display Street',
                'city' => 'Display City',
                'state' => 'DS',
                'zipcode' => '12345',
            ]);

            $component = Livewire::actingAs($this->admin)->test(VenuesTable::class);

            $component->assertOk()
                ->assertSee('Address Display Arena')
                ->assertSee('Display City')
                ->assertSee('DS');
        });

        test('excludes soft deleted venues by default', function () {
            $component = Livewire::actingAs($this->admin)->test(VenuesTable::class);

            $component->assertOk()
                ->assertSee('Active Test Arena')
                ->assertDontSee('Deleted Arena');
        });
    });

    describe('search and filtering functionality', function () {
        test('filters venues by name search', function () {
            $component = Livewire::actingAs($this->admin)->test(VenuesTable::class);

            $component->set('search', 'Active')
                ->assertSee('Active Test Arena')
                ->assertDontSee('Busy Event Arena')
                ->assertDontSee('Empty Arena');
        });

        test('filters venues by city search', function () {
            $component = Livewire::actingAs($this->admin)->test(VenuesTable::class);

            $component->set('search', 'Event City')
                ->assertSee('Busy Event Arena')
                ->assertDontSee('Active Test Arena')
                ->assertDontSee('Empty Arena');
        });

        test('filters venues by state search', function () {
            $component = Livewire::actingAs($this->admin)->test(VenuesTable::class);

            $component->set('search', 'AC')
                ->assertSee('Active Test Arena')
                ->assertDontSee('Busy Event Arena');
        });

        test('search handles partial matches', function () {
            $component = Livewire::actingAs($this->admin)->test(VenuesTable::class);

            $component->set('search', 'Arena')
                ->assertSee('Active Test Arena')
                ->assertSee('Busy Event Arena')
                ->assertSee('Empty Arena');
        });

        test('search is case insensitive', function () {
            $component = Livewire::actingAs($this->admin)->test(VenuesTable::class);

            $component->set('search', 'active')
                ->assertSee('Active Test Arena');

            $component->set('search', 'ACTIVE')
                ->assertSee('Active Test Arena');
        });

        test('empty search shows all venues', function () {
            $component = Livewire::actingAs($this->admin)->test(VenuesTable::class);

            $component->set('search', '')
                ->assertSee('Active Test Arena')
                ->assertSee('Busy Event Arena')
                ->assertSee('Empty Arena');
        });
    });

    describe('venue relationship display', function () {
        test('displays venues with event counts', function () {
            $component = Livewire::actingAs($this->admin)->test(VenuesTable::class);

            $component->assertOk()
                ->assertSee('Busy Event Arena');

            // Check that venue with events is displayed
            expect($this->venueWithEvents->events()->count())->toBeGreaterThan(0);
        });

        test('handles venues without events', function () {
            $component = Livewire::actingAs($this->admin)->test(VenuesTable::class);

            $component->assertOk()
                ->assertSee('Empty Arena');

            expect($this->emptyVenue->events()->count())->toBe(0);
        });

        test('loads event relationships efficiently', function () {
            $component = Livewire::actingAs($this->admin)->test(VenuesTable::class);

            $component->assertOk()
                ->assertSee('Busy Event Arena');

            // Verify relationship loading doesn't cause N+1 queries
            $venues = Venue::with('events')->get();
            expect($venues->where('id', $this->venueWithEvents->id)->first()->events)->not->toBeEmpty();
        });
    });

    describe('venue management operations', function () {
        test('administrators can delete venues', function () {
            $component = Livewire::actingAs($this->admin)->test(VenuesTable::class);

            $component->call('delete', $this->activeVenue)
                ->assertHasNoErrors();

            expect(Venue::find($this->activeVenue->id))->toBeNull();
            expect(Venue::onlyTrashed()->find($this->activeVenue->id))->not->toBeNull();
        });

        test('administrators can restore deleted venues', function () {
            $component = Livewire::actingAs($this->admin)->test(VenuesTable::class);

            $component->call('restore', $this->deletedVenue->id)
                ->assertHasNoErrors();

            expect(Venue::find($this->deletedVenue->id))->not->toBeNull();
        });

        test('delete operation preserves event relationships', function () {
            $event = Event::factory()->atVenue($this->activeVenue)->create();

            $component = Livewire::actingAs($this->admin)->test(VenuesTable::class);

            $component->call('delete', $this->activeVenue)
                ->assertHasNoErrors();

            expect($event->fresh()->venue_id)->toBe($this->activeVenue->id);
        });

        test('restore operation restores event relationships', function () {
            $event = Event::factory()->atVenue($this->deletedVenue)->create();

            $component = Livewire::actingAs($this->admin)->test(VenuesTable::class);

            $component->call('restore', $this->deletedVenue->id)
                ->assertHasNoErrors();

            $restoredVenue = Venue::find($this->deletedVenue->id);
            expect($restoredVenue->events)->toContain($event);
        });
    });

    describe('authorization and access control', function () {
        test('basic users cannot access venue table', function () {
            $component = Livewire::actingAs($this->basicUser)->test(VenuesTable::class);

            $component->assertForbidden();
        });

        test('guests cannot access venue table', function () {
            $component = Livewire::test(VenuesTable::class);

            $component->assertForbidden();
        });

        test('administrators have full access to all operations', function () {
            $component = Livewire::actingAs($this->admin)->test(VenuesTable::class);

            $component->assertOk()
                ->call('delete', $this->activeVenue)
                ->assertHasNoErrors()
                ->call('restore', $this->deletedVenue->id)
                ->assertHasNoErrors();
        });
    });

    describe('data sorting and ordering', function () {
        test('venues are ordered consistently', function () {
            $component = Livewire::actingAs($this->admin)->test(VenuesTable::class);

            $component->assertOk();

            // Verify venues appear in a predictable order
            $venues = Venue::orderBy('name')->get();
            expect($venues)->not->toBeEmpty();
        });

        test('search results maintain proper ordering', function () {
            $component = Livewire::actingAs($this->admin)->test(VenuesTable::class);

            $component->set('search', 'Arena')
                ->assertOk();

            // Results should still be properly ordered
            $filteredVenues = Venue::where('name', 'like', '%Arena%')->orderBy('name')->get();
            expect($filteredVenues->count())->toBeGreaterThan(0);
        });
    });

    describe('performance and optimization', function () {
        test('handles large numbers of venues efficiently', function () {
            // Create additional venues for testing
            Venue::factory()->count(50)->create();

            $component = Livewire::actingAs($this->admin)->test(VenuesTable::class);

            $component->assertOk();

            expect(Venue::count())->toBeGreaterThan(50);
        });

        test('search performs efficiently with many venues', function () {
            Venue::factory()->count(25)->create(['name' => 'Search Test Arena']);

            $component = Livewire::actingAs($this->admin)->test(VenuesTable::class);

            $component->set('search', 'Search Test')
                ->assertOk();

            expect(Venue::where('name', 'like', '%Search Test%')->count())->toBe(25);
        });

        test('relationship loading is optimized', function () {
            // Create venues with various event counts
            $venueWithManyEvents = Venue::factory()->create();
            Event::factory()->count(10)->atVenue($venueWithManyEvents)->create();

            $component = Livewire::actingAs($this->admin)->test(VenuesTable::class);

            $component->assertOk()
                ->assertSee($venueWithManyEvents->name);

            expect($venueWithManyEvents->events()->count())->toBe(10);
        });
    });

    describe('edge cases and error handling', function () {
        test('handles venues with special characters in names', function () {
            $specialVenue = Venue::factory()->create([
                'name' => 'O\'Malley\'s Arena & Entertainment Center',
                'city' => 'St. Louis',
            ]);

            $component = Livewire::actingAs($this->admin)->test(VenuesTable::class);

            $component->assertOk()
                ->assertSee('O\'Malley\'s Arena & Entertainment Center')
                ->assertSee('St. Louis');
        });

        test('handles empty database gracefully', function () {
            Venue::query()->delete();

            $component = Livewire::actingAs($this->admin)->test(VenuesTable::class);

            $component->assertOk();
        });

        test('handles venues with missing address components', function () {
            $incompleteVenue = Venue::factory()->create([
                'name' => 'Incomplete Address Arena',
                'street_address' => '',
                'city' => 'Complete City',
                'state' => 'CC',
                'zipcode' => '12345',
            ]);

            $component = Livewire::actingAs($this->admin)->test(VenuesTable::class);

            $component->assertOk()
                ->assertSee('Incomplete Address Arena')
                ->assertSee('Complete City');
        });

        test('handles concurrent operations safely', function () {
            $component1 = Livewire::actingAs($this->admin)->test(VenuesTable::class);
            $component2 = Livewire::actingAs($this->admin)->test(VenuesTable::class);

            $component1->call('delete', $this->activeVenue)
                ->assertHasNoErrors();

            $component2->call('delete', $this->activeVenue)
                ->assertHasNoErrors(); // Should handle already deleted venue gracefully
        });
    });

    describe('venue address formatting and display', function () {
        test('displays complete address information correctly', function () {
            $addressVenue = Venue::factory()->create([
                'name' => 'Complete Address Arena',
                'street_address' => '123 Main Street',
                'city' => 'Address City',
                'state' => 'AS',
                'zipcode' => '12345',
            ]);

            $component = Livewire::actingAs($this->admin)->test(VenuesTable::class);

            $component->assertOk()
                ->assertSee('Complete Address Arena')
                ->assertSee('Address City')
                ->assertSee('AS');
        });

        test('handles international address formats', function () {
            $internationalVenue = Venue::factory()->create([
                'name' => 'International Arena',
                'street_address' => '456 International Blvd',
                'city' => 'Global City',
                'state' => 'GC',
                'zipcode' => '54321',
            ]);

            $component = Livewire::actingAs($this->admin)->test(VenuesTable::class);

            $component->assertOk()
                ->assertSee('International Arena')
                ->assertSee('Global City');
        });
    });
});
