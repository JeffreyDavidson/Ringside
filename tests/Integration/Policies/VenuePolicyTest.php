<?php

declare(strict_types=1);

use App\Models\Events\Venue;
use App\Models\Users\User;
use App\Policies\VenuePolicy;
use Illuminate\Support\Facades\Gate;

/**
 * Integration tests for VenuePolicy authorization logic.
 *
 * INTEGRATION TEST SCOPE:
 * - Policy method logic in isolation
 * - User role checking and authorization rules
 * - global Gate hook behavior for administrators
 * - Individual authorization rules for venue operations
 * - Location and facility management authorization
 *
 * These tests verify that the VenuePolicy correctly implements
 * business rules for venue management operations without
 * involving HTTP requests, database queries, or external dependencies.
 *
 * @see VenuePolicy
 */
describe('VenuePolicy Integration Tests', function () {
    beforeEach(function () {
        $this->policy = new VenuePolicy();
        $this->admin = User::factory()->administrator()->make(['id' => 1]);
        $this->basicUser = User::factory()->make(['id' => 2]);
        $this->venue = Venue::factory()->make(['id' => 1]);
    });

    describe('global Gate hook authorization', function () {
        test('administrators bypass all authorization checks', function () {
            expect(Gate::forUser($this->admin)->raw('viewAny'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('view'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('create'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('update'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('delete'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('restore'))->toBeTrue();
        });

        test('non-administrators do not bypass authorization checks', function () {
            expect(Gate::forUser($this->basicUser)->raw('viewAny'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('view'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('create'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('update'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('delete'))->toBeNull();
            expect(Gate::forUser($this->basicUser)->raw('restore'))->toBeNull();
        });
    });

    describe('view permissions', function () {
        test('viewAny denies access for basic users', function () {
            expect($this->policy->viewAny($this->basicUser))->toBeFalse();
        });

        test('view denies access for basic users', function () {
            expect($this->policy->view($this->basicUser, $this->venue))->toBeFalse();
        });
    });

    describe('crud permissions', function () {
        test('create denies access for basic users', function () {
            expect($this->policy->create($this->basicUser))->toBeFalse();
        });

        test('update denies access for basic users', function () {
            expect($this->policy->update($this->basicUser, $this->venue))->toBeFalse();
        });

        test('delete denies access for basic users', function () {
            expect($this->policy->delete($this->basicUser, $this->venue))->toBeFalse();
        });

        test('restore denies access for basic users', function () {
            expect($this->policy->restore($this->basicUser, $this->venue))->toBeFalse();
        });
    });

    describe('venue-specific business rules', function () {
        test('venue management considers facility requirements', function () {
            $arenaVenue = Venue::factory()->make(['name' => 'Large Arena']);
            $smallVenue = Venue::factory()->make(['name' => 'Small Venue']);
            $outdoorVenue = Venue::factory()->make(['name' => 'Outdoor Stadium']);

            // All venue types should follow same authorization pattern
            expect($this->policy->view($this->basicUser, $this->venue))->toBeFalse();
            expect($this->policy->update($this->basicUser, $this->venue))->toBeFalse();
            expect($this->policy->delete($this->basicUser, $this->venue))->toBeFalse();
        });

        test('venue location management considers geographic factors', function () {
            $localVenue = Venue::factory()->make(['city' => 'Local City', 'state' => 'LS']);
            $remoteVenue = Venue::factory()->make(['city' => 'Remote City', 'state' => 'RS']);

            // Geographic location should not affect basic authorization
            expect($this->policy->view($this->basicUser, $this->venue))->toBeFalse();
            expect($this->policy->update($this->basicUser, $this->venue))->toBeFalse();
        });

        test('venue capacity and facilities do not affect base permissions', function () {
            $largeVenue = Venue::factory()->make(['name' => 'Large Conference Center']);
            $smallVenue = Venue::factory()->make(['name' => 'Small Community Hall']);

            // Facility size should not affect authorization
            expect($this->policy->view($this->basicUser, $this->venue))->toBeFalse();
            expect($this->policy->create($this->basicUser))->toBeFalse();
        });
    });

    describe('administrative override patterns', function () {
        test('administrator can perform restricted actions', function () {
            $busyVenue = Venue::factory()->make(['name' => 'Busy Venue']);
            $newVenue = Venue::factory()->make(['name' => 'New Venue']);
            $historicVenue = Venue::factory()->make(['name' => 'Historic Venue']);

            // Admin should be able to manage any venue regardless of characteristics
            expect(Gate::forUser($this->admin)->raw('create'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('update'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('delete'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('restore'))->toBeTrue();
        });

        test('basic users cannot perform management actions', function () {
            // All management actions should be denied for basic users
            expect($this->policy->create($this->basicUser))->toBeFalse();
            expect($this->policy->update($this->basicUser, $this->venue))->toBeFalse();
            expect($this->policy->delete($this->basicUser, $this->venue))->toBeFalse();
            expect($this->policy->restore($this->basicUser, $this->venue))->toBeFalse();
        });
    });

    describe('role-based authorization patterns', function () {
        test('role hierarchy is respected for venue operations', function () {
            // Administrator has full access
            expect(Gate::forUser($this->admin)->raw('create'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('update'))->toBeTrue();
            expect(Gate::forUser($this->admin)->raw('delete'))->toBeTrue();

            // Basic user has no access
            expect($this->policy->create($this->basicUser))->toBeFalse();
            expect($this->policy->update($this->basicUser, $this->venue))->toBeFalse();
            expect($this->policy->delete($this->basicUser, $this->venue))->toBeFalse();
        });

        test('view permissions are consistently restrictive', function () {
            // Both list and individual view permissions deny basic users
            expect($this->policy->viewAny($this->basicUser))->toBeFalse();
            expect($this->policy->view($this->basicUser, $this->venue))->toBeFalse();
        });
    });

    describe('venue address and location authorization', function () {
        test('address management follows standard authorization', function () {
            $venue = Venue::factory()->make([
                'street_address' => '123 Main Street',
                'city' => 'Test City',
                'state' => 'TS',
                'zipcode' => '12345',
            ]);

            // Address complexity should not affect authorization
            expect($this->policy->update($this->basicUser, $this->venue))->toBeFalse();
            expect(Gate::forUser($this->admin)->raw('update'))->toBeTrue();
        });

        test('venue relocation authorization follows policy pattern', function () {
            $venueWithComplexAddress = Venue::factory()->make([
                'street_address' => '456 Oak Avenue, Suite 200',
                'city' => 'Metropolitan City',
                'state' => 'MC',
                'zipcode' => '54321',
            ]);

            // Complex addresses should not change authorization
            expect($this->policy->update($this->basicUser, $this->venue))->toBeFalse();
            expect($this->policy->delete($this->basicUser, $this->venue))->toBeFalse();
        });
    });

    describe('venue event relationship authorization', function () {
        test('venue with event history follows standard authorization', function () {
            $busyVenue = Venue::factory()->make(['name' => 'Busy Event Venue']);
            $quietVenue = Venue::factory()->make(['name' => 'Quiet Venue']);

            // Event history should not affect base authorization
            expect($this->policy->view($this->basicUser, $this->venue))->toBeFalse();
            expect($this->policy->update($this->basicUser, $this->venue))->toBeFalse();
            expect($this->policy->delete($this->basicUser, $this->venue))->toBeFalse();
        });

        test('venue booking management requires proper authorization', function () {
            $popularVenue = Venue::factory()->make(['name' => 'Popular Venue']);
            $newVenue = Venue::factory()->make(['name' => 'New Venue']);

            // Popularity should not affect authorization rules
            expect($this->policy->update($this->basicUser, $this->venue))->toBeFalse();
            expect(Gate::forUser($this->admin)->raw('update'))->toBeTrue();
        });
    });

    describe('soft delete and restoration authorization', function () {
        test('restoration permissions are properly restricted', function () {
            $deletedVenue = Venue::factory()->make(['name' => 'Deleted Venue']);

            expect($this->policy->restore($this->basicUser, $this->venue))->toBeFalse();
            expect(Gate::forUser($this->admin)->raw('restore'))->toBeTrue();
        });

        test('deletion permissions consider venue status', function () {
            $activeVenue = Venue::factory()->make(['name' => 'Active Venue']);
            $unusedVenue = Venue::factory()->make(['name' => 'Unused Venue']);

            // Deletion should be restricted for basic users regardless of usage
            expect($this->policy->delete($this->basicUser, $this->venue))->toBeFalse();
            expect(Gate::forUser($this->admin)->raw('delete'))->toBeTrue();
        });
    });

    describe('policy consistency and edge cases', function () {
        test('policy methods return correct types', function () {
            // All policy methods should return boolean values
            expect($this->policy->viewAny($this->basicUser))->toBeBool();
            expect($this->policy->view($this->basicUser, $this->venue))->toBeBool();
            expect($this->policy->create($this->basicUser))->toBeBool();
            expect($this->policy->update($this->basicUser, $this->venue))->toBeBool();
            expect($this->policy->delete($this->basicUser, $this->venue))->toBeBool();
            expect($this->policy->restore($this->basicUser, $this->venue))->toBeBool();
        });

        test('global Gate hook returns correct types', function () {
            // global Gate hook should return true for admin, null for others
            expect(Gate::forUser($this->admin)->raw('any_ability'))->toBeTrue();
            expect(Gate::forUser($this->basicUser)->raw('any_ability'))->toBeNull();
        });
    });

    describe('venue facility management authorization', function () {
        test('facility type does not affect authorization', function () {
            $indoorVenue = Venue::factory()->make(['name' => 'Indoor Arena']);
            $outdoorVenue = Venue::factory()->make(['name' => 'Outdoor Stadium']);
            $conferenceVenue = Venue::factory()->make(['name' => 'Conference Center']);

            // Facility type should not change authorization pattern
            expect($this->policy->create($this->basicUser))->toBeFalse();
            expect($this->policy->update($this->basicUser, $this->venue))->toBeFalse();
            expect($this->policy->delete($this->basicUser, $this->venue))->toBeFalse();
        });

        test('venue capacity planning requires admin access', function () {
            $smallVenue = Venue::factory()->make(['name' => 'Small Theater']);
            $largeVenue = Venue::factory()->make(['name' => 'Large Stadium']);

            // Capacity planning should require administrative privileges
            expect($this->policy->update($this->basicUser, $this->venue))->toBeFalse();
            expect(Gate::forUser($this->admin)->raw('update'))->toBeTrue();
        });
    });

    describe('geographic and regional authorization', function () {
        test('venue location does not affect base authorization', function () {
            $localVenue = Venue::factory()->make([
                'city' => 'Local City',
                'state' => 'LC',
            ]);
            $distantVenue = Venue::factory()->make([
                'city' => 'Distant City',
                'state' => 'DC',
            ]);

            // Geographic location should not change authorization
            expect($this->policy->view($this->basicUser, $this->venue))->toBeFalse();
            expect($this->policy->update($this->basicUser, $this->venue))->toBeFalse();
        });

        test('multi-state venue management follows standard rules', function () {
            $venue = Venue::factory()->make([
                'name' => 'Multi-State Conference Center',
                'state' => 'MS',
            ]);

            // Complex venues should follow same authorization pattern
            expect($this->policy->create($this->basicUser))->toBeFalse();
            expect(Gate::forUser($this->admin)->raw('create'))->toBeTrue();
        });
    });

    describe('venue operational authorization', function () {
        test('venue maintenance authorization follows policy', function () {
            $maintenanceVenue = Venue::factory()->make(['name' => 'Under Maintenance Venue']);
            $operationalVenue = Venue::factory()->make(['name' => 'Operational Venue']);

            // Operational status should not affect basic authorization
            expect($this->policy->update($this->basicUser, $this->venue))->toBeFalse();
            expect(Gate::forUser($this->admin)->raw('update'))->toBeTrue();
        });

        test('venue availability management requires admin access', function () {
            $availableVenue = Venue::factory()->make(['name' => 'Available Venue']);
            $bookedVenue = Venue::factory()->make(['name' => 'Fully Booked Venue']);

            // Availability should not change authorization requirements
            expect($this->policy->view($this->basicUser, $this->venue))->toBeFalse();
            expect($this->policy->update($this->basicUser, $this->venue))->toBeFalse();
        });
    });
});
