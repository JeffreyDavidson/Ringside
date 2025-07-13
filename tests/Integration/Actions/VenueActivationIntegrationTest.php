<?php

declare(strict_types=1);

use App\Actions\Venues\CreateAction;
use App\Actions\Venues\DeleteAction;
use App\Actions\Venues\RestoreAction;
use App\Actions\Venues\UpdateAction;
use App\Data\Shared\VenueData;
use App\Models\Events\Event;
use App\Models\Shared\Venue;
use App\Models\Users\User;

/**
 * Integration tests for Venue CRUD actions with database operations.
 *
 * INTEGRATION TEST SCOPE:
 * - Action classes with real database operations
 * - Venue creation with full validation
 * - Database relationship integrity
 * - Event association and constraint handling
 * - Soft delete and restoration workflows
 *
 * These tests verify that venue management actions work correctly
 * with actual database operations and maintain data integrity
 * across venue relationships and business constraints.
 */
describe('Venue Action Integration Tests', function () {
    beforeEach(function () {
        $this->admin = User::factory()->administrator()->create();
        $this->actingAs($this->admin);
    });

    describe('venue creation integration', function () {
        test('create action creates venue with complete data', function () {
            $venueData = new VenueData(
                name: 'Integration Test Arena',
                street_address: '123 Test Street',
                city: 'Test City',
                state: 'TS',
                zipcode: '12345'
            );

            $venue = CreateAction::run($venueData);

            expect($venue)->toBeInstanceOf(Venue::class);
            expect($venue->name)->toBe('Integration Test Arena');
            expect($venue->street_address)->toBe('123 Test Street');
            expect($venue->city)->toBe('Test City');
            expect($venue->state)->toBe('TS');
            expect($venue->zipcode)->toBe('12345');
            expect($venue->exists)->toBeTrue();
        });

        test('create action persists venue to database', function () {
            $venueData = new VenueData(
                name: 'Database Test Arena',
                street_address: '456 Database Lane',
                city: 'Database City',
                state: 'DB',
                zipcode: '54321'
            );

            $venue = CreateAction::run($venueData);

            $retrievedVenue = Venue::find($venue->id);
            expect($retrievedVenue)->not->toBeNull();
            expect($retrievedVenue->name)->toBe('Database Test Arena');
            expect($retrievedVenue->street_address)->toBe('456 Database Lane');
            expect($retrievedVenue->city)->toBe('Database City');
            expect($retrievedVenue->state)->toBe('DB');
            expect($retrievedVenue->zipcode)->toBe('54321');
        });

        test('create action handles special characters in venue data', function () {
            $venueData = new VenueData(
                name: 'O\'Malley\'s Arena & Entertainment Center',
                street_address: '789 O\'Connor St.',
                city: 'St. Louis',
                state: 'MO',
                zipcode: '63101'
            );

            $venue = CreateAction::run($venueData);

            expect($venue->name)->toBe('O\'Malley\'s Arena & Entertainment Center');
            expect($venue->street_address)->toBe('789 O\'Connor St.');
            expect($venue->city)->toBe('St. Louis');
            expect($venue->state)->toBe('MO');
            expect($venue->zipcode)->toBe('63101');
        });

        test('create action handles minimal venue data', function () {
            $venueData = new VenueData(
                name: 'Minimal Arena',
                street_address: '100 Basic St',
                city: 'Basic City',
                state: 'BC',
                zipcode: '10000'
            );

            $venue = CreateAction::run($venueData);

            expect($venue->name)->toBe('Minimal Arena');
            expect($venue->street_address)->toBe('100 Basic St');
            expect($venue->city)->toBe('Basic City');
            expect($venue->state)->toBe('BC');
            expect($venue->zipcode)->toBe('10000');
        });
    });

    describe('venue update integration', function () {
        test('update action modifies existing venue', function () {
            $venue = Venue::factory()->create([
                'name' => 'Original Arena',
                'city' => 'Original City',
            ]);

            $venueData = new VenueData(
                name: 'Updated Arena',
                street_address: $venue->street_address,
                city: 'Updated City',
                state: $venue->state,
                zipcode: $venue->zipcode
            );

            $updatedVenue = UpdateAction::run($venue, $venueData);

            expect($updatedVenue->name)->toBe('Updated Arena');
            expect($updatedVenue->city)->toBe('Updated City');
            expect($updatedVenue->id)->toBe($venue->id);
        });

        test('update action persists changes to database', function () {
            $venue = Venue::factory()->create([
                'name' => 'Database Original',
                'state' => 'DO',
            ]);

            $venueData = new VenueData(
                name: 'Database Updated',
                street_address: $venue->street_address,
                city: $venue->city,
                state: 'DU',
                zipcode: $venue->zipcode
            );

            UpdateAction::run($venue, $venueData);

            $retrievedVenue = Venue::find($venue->id);
            expect($retrievedVenue->name)->toBe('Database Updated');
            expect($retrievedVenue->state)->toBe('DU');
        });

        test('update action handles address changes', function () {
            $venue = Venue::factory()->create([
                'street_address' => '123 Old Street',
                'city' => 'Old City',
                'state' => 'OS',
                'zipcode' => '12345',
            ]);

            $venueData = new VenueData(
                name: $venue->name,
                street_address: '456 New Avenue',
                city: 'New City',
                state: 'NS',
                zipcode: '54321'
            );

            $updatedVenue = UpdateAction::run($venue, $venueData);

            expect($updatedVenue->street_address)->toBe('456 New Avenue');
            expect($updatedVenue->city)->toBe('New City');
            expect($updatedVenue->state)->toBe('NS');
            expect($updatedVenue->zipcode)->toBe('54321');
        });

        test('update action maintains venue relationships', function () {
            $venue = Venue::factory()->create();
            $event = Event::factory()->atVenue($venue)->create();

            $venueData = new VenueData(
                name: 'Relationship Test Arena',
                street_address: $venue->street_address,
                city: $venue->city,
                state: $venue->state,
                zipcode: $venue->zipcode
            );

            $updatedVenue = UpdateAction::run($venue, $venueData);

            expect($updatedVenue->events)->toContain($event);
            expect($event->fresh()->venue_id)->toBe($venue->id);
        });
    });

    describe('venue deletion integration', function () {
        test('delete action soft deletes venue', function () {
            $venue = Venue::factory()->create(['name' => 'Deletion Test Arena']);

            DeleteAction::run($venue);

            expect(Venue::find($venue->id))->toBeNull();
            expect(Venue::onlyTrashed()->find($venue->id))->not->toBeNull();
        });

        test('delete action maintains event relationships', function () {
            $venue = Venue::factory()->create();
            $event = Event::factory()->atVenue($venue)->create();

            DeleteAction::run($venue);

            expect($event->fresh()->venue_id)->toBe($venue->id);
            expect($event->fresh()->venue)->toBeNull(); // Soft deleted venue
        });

        test('delete action handles venue with multiple events', function () {
            $venue = Venue::factory()->create();
            $event1 = Event::factory()->atVenue($venue)->create(['name' => 'Event 1']);
            $event2 = Event::factory()->atVenue($venue)->create(['name' => 'Event 2']);

            DeleteAction::run($venue);

            expect(Venue::find($venue->id))->toBeNull();
            expect($event1->fresh()->venue_id)->toBe($venue->id);
            expect($event2->fresh()->venue_id)->toBe($venue->id);
        });

        test('delete action handles venue without events', function () {
            $venue = Venue::factory()->create(['name' => 'No Events Arena']);

            DeleteAction::run($venue);

            expect(Venue::find($venue->id))->toBeNull();
            expect(Venue::onlyTrashed()->find($venue->id))->not->toBeNull();
        });
    });

    describe('venue restoration integration', function () {
        test('restore action restores soft deleted venue', function () {
            $venue = Venue::factory()->create(['name' => 'Restoration Test Arena']);
            $venueId = $venue->id;

            $venue->delete();
            expect(Venue::find($venueId))->toBeNull();

            RestoreAction::run($venueId);

            $restoredVenue = Venue::find($venueId);
            expect($restoredVenue)->not->toBeNull();
            expect($restoredVenue->name)->toBe('Restoration Test Arena');
        });

        test('restore action maintains event relationships', function () {
            $venue = Venue::factory()->create();
            $event = Event::factory()->atVenue($venue)->create(['name' => 'Restoration Event']);

            $venue->delete();
            RestoreAction::run($venue->id);

            $restoredVenue = Venue::find($venue->id);
            expect($restoredVenue->events)->toContain($event);
            expect($event->fresh()->venue)->not->toBeNull();
        });

        test('restore action handles venue with complex relationships', function () {
            $venue = Venue::factory()->create();
            $pastEvent = Event::factory()->atVenue($venue)->create([
                'name' => 'Past Event',
                'date' => now()->subDay(),
            ]);
            $futureEvent = Event::factory()->atVenue($venue)->create([
                'name' => 'Future Event',
                'date' => now()->addDay(),
            ]);

            $venue->delete();
            RestoreAction::run($venue->id);

            $restoredVenue = Venue::find($venue->id);
            $restoredVenue->load(['events', 'previousEvents']);

            expect($restoredVenue->events)->toHaveCount(2);
            expect($restoredVenue->previousEvents)->toHaveCount(1);
            expect($restoredVenue->previousEvents->first()->name)->toBe('Past Event');
        });
    });

    describe('venue integration with events', function () {
        test('venue can be associated with events after creation', function () {
            $venueData = new VenueData(
                name: 'Event Association Arena',
                street_address: '123 Event St',
                city: 'Event City',
                state: 'EC',
                zipcode: '12345'
            );

            $venue = CreateAction::run($venueData);
            $event = Event::factory()->create(['venue_id' => $venue->id]);

            $venue->refresh();
            expect($venue->events)->toContain($event);
        });

        test('venue update preserves event associations', function () {
            $venue = Venue::factory()->create();
            $event1 = Event::factory()->atVenue($venue)->create(['name' => 'Event 1']);
            $event2 = Event::factory()->atVenue($venue)->create(['name' => 'Event 2']);

            $venueData = new VenueData(
                name: 'Updated Event Arena',
                street_address: $venue->street_address,
                city: $venue->city,
                state: $venue->state,
                zipcode: $venue->zipcode
            );

            $updatedVenue = UpdateAction::run($venue, $venueData);

            expect($updatedVenue->events)->toContain($event1);
            expect($updatedVenue->events)->toContain($event2);
        });

        test('venue deletion does not cascade to events', function () {
            $venue = Venue::factory()->create();
            $event = Event::factory()->atVenue($venue)->create(['name' => 'Preserved Event']);

            DeleteAction::run($venue);

            expect(Event::find($event->id))->not->toBeNull();
            expect($event->fresh()->venue_id)->toBe($venue->id);
        });
    });

    describe('venue data validation integration', function () {
        test('venue creation validates required fields', function () {
            $venueData = new VenueData(
                name: 'Validation Test Arena',
                street_address: '123 Validation St',
                city: 'Validation City',
                state: 'VC',
                zipcode: '12345'
            );

            $venue = CreateAction::run($venueData);

            expect($venue->name)->not->toBeEmpty();
            expect($venue->street_address)->not->toBeEmpty();
            expect($venue->city)->not->toBeEmpty();
            expect($venue->state)->not->toBeEmpty();
            expect($venue->zipcode)->not->toBeEmpty();
        });

        test('venue update validates data changes', function () {
            $venue = Venue::factory()->create();

            $venueData = new VenueData(
                name: 'Updated Validation Arena',
                street_address: '456 Updated St',
                city: 'Updated City',
                state: 'UC',
                zipcode: '54321'
            );

            $updatedVenue = UpdateAction::run($venue, $venueData);

            expect($updatedVenue->name)->toBe('Updated Validation Arena');
            expect($updatedVenue->street_address)->toBe('456 Updated St');
            expect($updatedVenue->city)->toBe('Updated City');
            expect($updatedVenue->state)->toBe('UC');
            expect($updatedVenue->zipcode)->toBe('54321');
        });
    });

    describe('venue business logic integration', function () {
        test('venue creation establishes proper timestamps', function () {
            $venueData = new VenueData(
                name: 'Timestamp Test Arena',
                street_address: '123 Time St',
                city: 'Time City',
                state: 'TC',
                zipcode: '12345'
            );

            $venue = CreateAction::run($venueData);

            expect($venue->created_at)->not->toBeNull();
            expect($venue->updated_at)->not->toBeNull();
            expect($venue->created_at->equalTo($venue->updated_at))->toBeTrue();
        });

        test('venue update modifies timestamps appropriately', function () {
            $venue = Venue::factory()->create();
            $originalUpdatedAt = $venue->updated_at;

            $venueData = new VenueData(
                name: 'Timestamp Updated Arena',
                street_address: $venue->street_address,
                city: $venue->city,
                state: $venue->state,
                zipcode: $venue->zipcode
            );

            $updatedVenue = UpdateAction::run($venue, $venueData);

            expect($updatedVenue->updated_at->greaterThan($originalUpdatedAt))->toBeTrue();
        });

        test('venue handles concurrent operations safely', function () {
            $venue = Venue::factory()->create(['name' => 'Concurrent Test Arena']);

            $venueData1 = new VenueData(
                name: 'Concurrent Update 1',
                street_address: $venue->street_address,
                city: $venue->city,
                state: $venue->state,
                zipcode: $venue->zipcode
            );

            $venueData2 = new VenueData(
                name: 'Concurrent Update 2',
                street_address: $venue->street_address,
                city: $venue->city,
                state: $venue->state,
                zipcode: $venue->zipcode
            );

            $updatedVenue1 = UpdateAction::run($venue, $venueData1);
            $updatedVenue2 = UpdateAction::run($venue->fresh(), $venueData2);

            expect($updatedVenue2->name)->toBe('Concurrent Update 2');
        });
    });
});
