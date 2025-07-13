<?php

declare(strict_types=1);

use App\Data\Shared\VenueData;
use App\Models\Shared\Venue;
use App\Repositories\Contracts\VenueRepositoryInterface;
use App\Repositories\VenueRepository;

use function Spatie\PestPluginTestTime\testTime;

/**
 * Unit tests for VenueRepository business logic and data operations.
 *
 * UNIT TEST SCOPE:
 * - Repository configuration and structure verification
 * - Core CRUD operations (create, update, delete, restore)
 * - Venue specific business logic and address handling
 *
 * These tests verify that the VenueRepository correctly implements
 * all business operations and data persistence requirements.
 *
 * @see VenueRepository
 */
describe('VenueRepository Unit Tests', function () {
    beforeEach(function () {
        testTime()->freeze();
        $this->repository = app(VenueRepository::class);
    });

    describe('repository configuration', function () {
        test('repository can be resolved from container', function () {
            expect($this->repository)->toBeInstanceOf(VenueRepository::class);
            expect($this->repository)->toBeInstanceOf(VenueRepositoryInterface::class);
        });

        test('repository has all expected methods', function () {
            $methods = [
                'create', 'update', 'delete', 'restore'
            ];

            foreach ($methods as $method) {
                expect(method_exists($this->repository, $method))
                    ->toBeTrue("Repository should have {$method} method");
            }
        });
    });

    describe('core CRUD operations', function () {
        test('can create venue with complete address data', function () {
            // Arrange
            $data = new VenueData('Example Venue Name', '123 Main Street', 'Laraville', 'New York', '12345');

            // Act
            $venue = $this->repository->create($data);

            // Assert
            expect($venue)
                ->toBeInstanceOf(Venue::class)
                ->name->toEqual('Example Venue Name')
                ->street_address->toEqual('123 Main Street')
                ->city->toEqual('Laraville')
                ->state->toEqual('New York')
                ->zipcode->toEqual('12345');

            $this->assertDatabaseHas('venues', [
                'name' => 'Example Venue Name',
                'street_address' => '123 Main Street',
                'city' => 'Laraville',
                'state' => 'New York',
                'zipcode' => '12345',
            ]);
        });

        test('can create venue with minimal address data', function () {
            // Arrange
            $data = new VenueData('Simple Venue', 'Street Only', 'City Only', 'ST', '00000');

            // Act
            $venue = $this->repository->create($data);

            // Assert
            expect($venue)
                ->name->toBe('Simple Venue')
                ->street_address->toBe('Street Only')
                ->city->toBe('City Only')
                ->state->toBe('ST')
                ->zipcode->toBe('00000');

            $this->assertDatabaseHas('venues', [
                'name' => 'Simple Venue',
                'street_address' => 'Street Only',
                'city' => 'City Only',
                'state' => 'ST',
                'zipcode' => '00000',
            ]);
        });

        test('can update existing venue', function () {
            // Arrange
            $venue = Venue::factory()->create();
            $data = new VenueData('Updated Venue Name', '456 Updated Street', 'Updated City', 'CA', '90210');

            // Act
            $updatedVenue = $this->repository->update($venue, $data);

            // Assert
            expect($updatedVenue->fresh())
                ->name->toBe('Updated Venue Name')
                ->street_address->toBe('456 Updated Street')
                ->city->toBe('Updated City')
                ->state->toBe('CA')
                ->zipcode->toBe('90210');

            $this->assertDatabaseHas('venues', [
                'id' => $venue->id,
                'name' => 'Updated Venue Name',
                'street_address' => '456 Updated Street',
                'city' => 'Updated City',
                'state' => 'CA',
                'zipcode' => '90210',
            ]);
        });

        test('can soft delete venue', function () {
            // Arrange
            $venue = Venue::factory()->create();

            // Act
            $this->repository->delete($venue);

            // Assert
            expect($venue->fresh()->deleted_at)->not->toBeNull();
            $this->assertSoftDeleted('venues', ['id' => $venue->id]);
        });

        test('can restore soft deleted venue', function () {
            // Arrange
            $venue = Venue::factory()->trashed()->create();

            // Act
            $this->repository->restore($venue);

            // Assert
            expect($venue->fresh()->deleted_at)->toBeNull();
            $this->assertDatabaseHas('venues', [
                'id' => $venue->id,
                'deleted_at' => null,
            ]);
        });
    });

    describe('address handling', function () {
        test('handles various address formats correctly', function () {
            // Arrange - Test different address scenarios
            $scenarios = [
                ['venue' => 'Madison Square Garden', 'street' => '4 Pennsylvania Plaza', 'city' => 'New York', 'state' => 'NY', 'zip' => '10001'],
                ['venue' => 'Staples Center', 'street' => '1111 S Figueroa St', 'city' => 'Los Angeles', 'state' => 'CA', 'zip' => '90015'],
                ['venue' => 'Local Gym', 'street' => '123 Main St', 'city' => 'Smalltown', 'state' => 'TX', 'zip' => '12345'],
            ];

            foreach ($scenarios as $scenario) {
                // Act
                $data = new VenueData(
                    $scenario['venue'],
                    $scenario['street'],
                    $scenario['city'],
                    $scenario['state'],
                    $scenario['zip']
                );
                $venue = $this->repository->create($data);

                // Assert
                expect($venue)
                    ->name->toBe($scenario['venue'])
                    ->street_address->toBe($scenario['street'])
                    ->city->toBe($scenario['city'])
                    ->state->toBe($scenario['state'])
                    ->zipcode->toBe($scenario['zip']);

                $this->assertDatabaseHas('venues', [
                    'name' => $scenario['venue'],
                    'street_address' => $scenario['street'],
                    'city' => $scenario['city'],
                    'state' => $scenario['state'],
                    'zipcode' => $scenario['zip'],
                ]);
            }
        });

        test('preserves address data integrity during updates', function () {
            // Arrange
            $venue = Venue::factory()->create([
                'name' => 'Original Venue',
                'street_address' => 'Original Street',
                'city' => 'Original City',
                'state' => 'OR',
                'zipcode' => '11111',
            ]);

            // Act - Update only some fields
            $data = new VenueData('Updated Name', 'Updated Street', 'Updated City', 'UP', '22222');
            $this->repository->update($venue, $data);

            // Assert - All fields should be updated
            expect($venue->fresh())
                ->name->toBe('Updated Name')
                ->street_address->toBe('Updated Street')
                ->city->toBe('Updated City')
                ->state->toBe('UP')
                ->zipcode->toBe('22222');
        });

        test('handles special characters in address fields', function () {
            // Arrange
            $data = new VenueData(
                'Venue with "Quotes" & Symbols',
                '123 O\'Malley Street #5',
                'Saint-Jean-sur-Richelieu',
                'QC',
                'H3B-2Y7'
            );

            // Act
            $venue = $this->repository->create($data);

            // Assert
            expect($venue)
                ->name->toBe('Venue with "Quotes" & Symbols')
                ->street_address->toBe('123 O\'Malley Street #5')
                ->city->toBe('Saint-Jean-sur-Richelieu')
                ->state->toBe('QC')
                ->zipcode->toBe('H3B-2Y7');

            $this->assertDatabaseHas('venues', [
                'name' => 'Venue with "Quotes" & Symbols',
                'street_address' => '123 O\'Malley Street #5',
                'city' => 'Saint-Jean-sur-Richelieu',
                'state' => 'QC',
                'zipcode' => 'H3B-2Y7',
            ]);
        });
    });

    describe('data persistence verification', function () {
        test('create operation returns venue with correct attributes', function () {
            // Arrange
            $data = new VenueData('Test Venue', 'Test Street', 'Test City', 'TS', '00000');

            // Act
            $venue = $this->repository->create($data);

            // Assert
            expect($venue)
                ->toBeInstanceOf(Venue::class)
                ->exists->toBeTrue()
                ->id->not->toBeNull()
                ->created_at->not->toBeNull()
                ->updated_at->not->toBeNull()
                ->deleted_at->toBeNull();
        });

        test('update operation preserves venue identity', function () {
            // Arrange
            $venue = Venue::factory()->create();
            $originalId = $venue->id;
            $originalCreatedAt = $venue->created_at;
            $data = new VenueData('Updated Venue', 'Updated Street', 'Updated City', 'UP', '11111');

            // Act
            $updatedVenue = $this->repository->update($venue, $data);

            // Assert
            expect($updatedVenue)
                ->id->toBe($originalId)
                ->created_at->eq($originalCreatedAt);
            
            // Verify updated_at timestamp
            expect($updatedVenue->updated_at)->not->toBeNull();
            expect($updatedVenue->updated_at->greaterThanOrEqualTo($originalCreatedAt))->toBeTrue();
        });

        test('soft delete preserves venue data', function () {
            // Arrange
            $venue = Venue::factory()->create([
                'name' => 'Venue To Delete',
                'street_address' => 'Delete Street',
            ]);
            $originalData = $venue->only(['name', 'street_address', 'city', 'state', 'zipcode']);

            // Act
            $this->repository->delete($venue);

            // Assert
            $freshVenue = $venue->fresh();
            expect($freshVenue)
                ->deleted_at->not->toBeNull()
                ->name->toBe($originalData['name'])
                ->street_address->toBe($originalData['street_address']);
        });

        test('restore operation clears deleted_at timestamp', function () {
            // Arrange
            $venue = Venue::factory()->trashed()->create();
            expect($venue->deleted_at)->not->toBeNull();

            // Act
            $this->repository->restore($venue);

            // Assert
            expect($venue->fresh())
                ->deleted_at->toBeNull()
                ->exists->toBeTrue();
        });
    });
});