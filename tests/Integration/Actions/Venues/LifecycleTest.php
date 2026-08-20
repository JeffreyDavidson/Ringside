<?php

declare(strict_types=1);

use App\Actions\Venues\CreateAction;
use App\Actions\Venues\DeleteAction;
use App\Actions\Venues\RestoreAction;
use App\Actions\Venues\UpdateAction;
use App\Data\Events\VenueData;
use App\Exceptions\Events\CannotBeRestoredException;
use App\Models\Events\Event;
use App\Models\Events\Venue;
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
                state: 'Texas',
                zipcode: '12345'
            );

            $venue = resolve(CreateAction::class)->handle($venueData);

            expect($venue)->toBeInstanceOf(Venue::class);
            expect($venue->name)->toBe('Integration Test Arena');
            expect($venue->street_address)->toBe('123 Test Street');
            expect($venue->city)->toBe('Test City');
            expect($venue->state)->toBe('Texas');
            expect($venue->zipcode)->toBe('12345');
            expect($venue->exists)->toBeTrue();
        });

        test('create action persists venue to database', function () {
            $venueData = new VenueData(
                name: 'Database Test Arena',
                street_address: '456 Database Lane',
                city: 'Database City',
                state: 'Delaware',
                zipcode: '54321'
            );

            $venue = resolve(CreateAction::class)->handle($venueData);

            $retrievedVenue = Venue::query()->whereKey($venue->getKey())->firstOrFail();
            expect($retrievedVenue->name)->toBe('Database Test Arena');
            expect($retrievedVenue->street_address)->toBe('456 Database Lane');
            expect($retrievedVenue->city)->toBe('Database City');
            expect($retrievedVenue->state)->toBe('Delaware');
            expect($retrievedVenue->zipcode)->toBe('54321');
        });

        test('create action handles special characters in venue data', function () {
            $venueData = new VenueData(
                name: 'O\'Malley\'s Arena & Entertainment Center',
                street_address: '789 O\'Connor St.',
                city: 'St. Louis',
                state: 'Missouri',
                zipcode: '63101'
            );

            $venue = resolve(CreateAction::class)->handle($venueData);

            expect($venue->name)->toBe('O\'Malley\'s Arena & Entertainment Center');
            expect($venue->street_address)->toBe('789 O\'Connor St.');
            expect($venue->city)->toBe('St. Louis');
            expect($venue->state)->toBe('Missouri');
            expect($venue->zipcode)->toBe('63101');
        });

        test('create action handles minimal venue data', function () {
            $venueData = new VenueData(
                name: 'Minimal Arena',
                street_address: '100 Basic St',
                city: 'Basic City',
                state: 'California',
                zipcode: '10000'
            );

            $venue = resolve(CreateAction::class)->handle($venueData);

            expect($venue->name)->toBe('Minimal Arena');
            expect($venue->street_address)->toBe('100 Basic St');
            expect($venue->city)->toBe('Basic City');
            expect($venue->state)->toBe('California');
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

            $updatedVenue = resolve(UpdateAction::class)->handle($venue, $venueData);

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
                state: 'Utah',
                zipcode: $venue->zipcode
            );

            resolve(UpdateAction::class)->handle($venue, $venueData);

            $retrievedVenue = Venue::findOrFail($venue->id);
            expect($retrievedVenue->name)->toBe('Database Updated');
            expect($retrievedVenue->state)->toBe('Utah');
        });

        test('update action uses the current persisted venue state', function () {
            $venue = Venue::factory()->create(['name' => 'Original Arena']);
            $staleVenue = $venue->replicate(['id']);
            $staleVenue->id = $venue->id;
            $staleVenue->exists = true;
            $venueData = new VenueData(
                name: 'Updated Arena',
                street_address: $venue->street_address,
                city: $venue->city,
                state: $venue->state,
                zipcode: $venue->zipcode
            );

            $updatedVenue = resolve(UpdateAction::class)->handle($staleVenue, $venueData);

            expect($updatedVenue->getKey())->toBe($venue->getKey())
                ->and(Venue::findOrFail($venue->getKey())->name)->toBe('Updated Arena');
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
                state: 'Nevada',
                zipcode: '54321'
            );

            $updatedVenue = resolve(UpdateAction::class)->handle($venue, $venueData);

            expect($updatedVenue->street_address)->toBe('456 New Avenue');
            expect($updatedVenue->city)->toBe('New City');
            expect($updatedVenue->state)->toBe('Nevada');
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

            $updatedVenue = resolve(UpdateAction::class)->handle($venue, $venueData);

            expect($updatedVenue->events->pluck('id'))->toContain($event->id);
            expect(freshModel($event)->venue_id)->toBe($venue->id);
        });
    });

    describe('venue deletion integration', function () {
        test('delete action soft deletes venue', function () {
            $venue = Venue::factory()->create(['name' => 'Deletion Test Arena']);

            resolve(DeleteAction::class)->handle($venue);

            expect(Venue::find($venue->id))->toBeNull();
            expect(Venue::onlyTrashed()->find($venue->id))->not()->toBeNull();
        });

        test('delete action maintains event relationships', function () {
            $venue = Venue::factory()->create();
            $event = Event::factory()->atVenue($venue)->create();

            resolve(DeleteAction::class)->handle($venue);

            expect(freshModel($event)->venue_id)->toBe($venue->id);
            expect(freshModel($event)->venue)->toBeNull(); // Soft deleted venue
        });

        test('delete action handles venue with multiple events', function () {
            $venue = Venue::factory()->create();
            $event1 = Event::factory()->atVenue($venue)->create(['name' => 'Event 1']);
            $event2 = Event::factory()->atVenue($venue)->create(['name' => 'Event 2']);

            resolve(DeleteAction::class)->handle($venue);

            expect(Venue::find($venue->id))->toBeNull();
            expect(freshModel($event1)->venue_id)->toBe($venue->id);
            expect(freshModel($event2)->venue_id)->toBe($venue->id);
        });

        test('delete action handles venue without events', function () {
            $venue = Venue::factory()->create(['name' => 'No Events Arena']);

            resolve(DeleteAction::class)->handle($venue);

            expect(Venue::find($venue->id))->toBeNull();
            expect(Venue::onlyTrashed()->find($venue->id))->not()->toBeNull();
        });
    });

    describe('venue restoration integration', function () {
        test('restore action restores soft deleted venue', function () {
            $venue = Venue::factory()->create(['name' => 'Restoration Test Arena']);
            $venueId = $venue->id;

            $venue->delete();
            expect(Venue::find($venueId))->toBeNull();

            $deletedVenue = Venue::onlyTrashed()->findOrFail($venueId);
            resolve(RestoreAction::class)->handle($deletedVenue);

            $restoredVenue = Venue::findOrFail($venueId);
            expect($restoredVenue->name)->toBe('Restoration Test Arena');
        });

        test('restore action rejects an active venue name conflict', function () {
            $venue = Venue::factory()->create(['name' => 'Restoration Test Arena']);

            resolve(DeleteAction::class)->handle($venue);
            Venue::factory()->create(['name' => 'Restoration Test Arena']);

            expect(fn () => resolve(RestoreAction::class)->handle($venue))
                ->toThrow(CannotBeRestoredException::class);

            expect(Venue::onlyTrashed()->whereKey($venue->getKey())->exists())->toBeTrue();
        });

        test('restore action maintains event relationships', function () {
            $venue = Venue::factory()->create();
            $event = Event::factory()->atVenue($venue)->create(['name' => 'Restoration Event']);

            $venue->delete();
            $deletedVenue = Venue::onlyTrashed()->findOrFail($venue->id);
            resolve(RestoreAction::class)->handle($deletedVenue);

            $restoredVenue = Venue::findOrFail($venue->id);
            expect($restoredVenue->events->pluck('id'))->toContain($event->id);
            expect(freshModel($event)->venue)->not()->toBeNull();
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
            $deletedVenue = Venue::onlyTrashed()->findOrFail($venue->id);
            resolve(RestoreAction::class)->handle($deletedVenue);

            $restoredVenue = Venue::query()
                ->with('events')
                ->findOrFail($venue->id);

            expect($restoredVenue->events)->toHaveCount(2)
                ->and($restoredVenue->events->modelKeys())->toEqualCanonicalizing([
                    $pastEvent->id,
                    $futureEvent->id,
                ]);
        });
    });

    describe('venue integration with events', function () {
        test('venue can be associated with events after creation', function () {
            $venueData = new VenueData(
                name: 'Event Association Arena',
                street_address: '123 Event St',
                city: 'Event City',
                state: 'Colorado',
                zipcode: '12345'
            );

            $venue = resolve(CreateAction::class)->handle($venueData);
            $event = Event::factory()->create(['venue_id' => $venue->id]);

            $venue->refresh();
            expect($venue->events->pluck('id'))->toContain($event->id);
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

            $updatedVenue = resolve(UpdateAction::class)->handle($venue, $venueData);

            expect($updatedVenue->events->pluck('id'))->toContain($event1->id);
            expect($updatedVenue->events->pluck('id'))->toContain($event2->id);
        });

        test('venue deletion does not cascade to events', function () {
            $venue = Venue::factory()->create();
            $event = Event::factory()->atVenue($venue)->create(['name' => 'Preserved Event']);

            resolve(DeleteAction::class)->handle($venue);

            expect(Event::find($event->id))->not()->toBeNull();
            expect(freshModel($event)->venue_id)->toBe($venue->id);
        });
    });

    describe('venue data validation integration', function () {
        test('venue creation validates required fields', function () {
            $venueData = new VenueData(
                name: 'Validation Test Arena',
                street_address: '123 Validation St',
                city: 'Validation City',
                state: 'Virginia',
                zipcode: '12345'
            );

            $venue = resolve(CreateAction::class)->handle($venueData);

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
                state: 'Connecticut',
                zipcode: '54321'
            );

            $updatedVenue = resolve(UpdateAction::class)->handle($venue, $venueData);

            expect($updatedVenue->name)->toBe('Updated Validation Arena');
            expect($updatedVenue->street_address)->toBe('456 Updated St');
            expect($updatedVenue->city)->toBe('Updated City');
            expect($updatedVenue->state)->toBe('Connecticut');
            expect($updatedVenue->zipcode)->toBe('54321');
        });
    });

    describe('venue business logic integration', function () {
        test('venue creation establishes proper timestamps', function () {
            $venueData = new VenueData(
                name: 'Timestamp Test Arena',
                street_address: '123 Time St',
                city: 'Time City',
                state: 'Tennessee',
                zipcode: '12345'
            );

            $venue = resolve(CreateAction::class)->handle($venueData);

            expect($venue->created_at)->not()->toBeNull();
            expect($venue->updated_at)->not()->toBeNull();
            expect(requiredDate($venue->created_at)->format('Y-m-d H:i:s'))->toBe(requiredDate($venue->updated_at)->format('Y-m-d H:i:s'));
        });

        test('venue update modifies timestamps appropriately', function () {
            $venue = Venue::factory()->create(['name' => 'Original Name']);
            $originalUpdatedAt = $venue->updated_at;

            // Wait for next second to ensure timestamp difference
            sleep(1);

            $venueData = new VenueData(
                name: 'Timestamp Updated Arena',
                street_address: $venue->street_address,
                city: $venue->city,
                state: $venue->state,
                zipcode: $venue->zipcode
            );

            $updatedVenue = resolve(UpdateAction::class)->handle($venue, $venueData);

            // Verify the name actually changed to confirm update happened
            expect($updatedVenue->name)->toBe('Timestamp Updated Arena');
            expect(requiredDate($updatedVenue->updated_at)->isAfter(requiredDate($originalUpdatedAt)))->toBeTrue();
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

            $updatedVenue1 = resolve(UpdateAction::class)->handle($venue, $venueData1);
            $updatedVenue2 = resolve(UpdateAction::class)->handle(freshModel($venue), $venueData2);

            expect($updatedVenue2->name)->toBe('Concurrent Update 2');
        });
    });
});
