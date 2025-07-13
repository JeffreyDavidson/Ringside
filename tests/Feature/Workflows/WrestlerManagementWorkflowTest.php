<?php

declare(strict_types=1);

use App\Livewire\Wrestlers\Modals\WrestlerFormModal;
use App\Livewire\Wrestlers\Tables\WrestlersTable;
use App\Models\Wrestlers\Wrestler;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

/**
 * Feature tests for complete wrestler management workflows.
 * Tests realistic user journeys for creating, managing, and updating wrestlers throughout their careers.
 */
describe('Wrestler Creation Journey', function () {
    test('administrator can create wrestler through complete UI workflow', function () {
        // Given: An authenticated administrator
        $admin = administrator();

        // When: Navigating to wrestlers index
        actingAs($admin)
            ->get(route('wrestlers.index'))
            ->assertOk()
            ->assertSeeLivewire(WrestlersTable::class);

        // And: Creating wrestler through modal workflow
        $modalComponent = Livewire::actingAs($admin)
            ->test(WrestlerFormModal::class)
            ->call('openModal') // Open for creation
            ->assertSet('isModalOpen', true);

        // And: Filling out the wrestler form
        $wrestlerData = [
            'name' => 'John Cena',
            'hometown' => 'West Newbury, MA',
            'height_feet' => 6,
            'height_inches' => 1,
            'weight' => 251,
            'signature_move' => 'Attitude Adjustment',
            'employment_date' => now()->format('Y-m-d'),
        ];

        foreach ($wrestlerData as $field => $value) {
            $modalComponent->set("form.{$field}", $value);
        }

        // And: Submitting the form
        $modalComponent
            ->call('submitForm')
            ->assertHasNoErrors()
            ->assertSet('isModalOpen', false)
            ->assertDispatched('form-submitted');

        // Then: Wrestler should be created in database
        expect(Wrestler::where('name', 'John Cena')->exists())->toBeTrue();

        $wrestler = Wrestler::where('name', 'John Cena')->first();
        expect($wrestler->hometown)->toBe('West Newbury, MA');
        expect($wrestler->height->toInches())->toBe(73); // 6'1" = 73 inches
        expect($wrestler->weight)->toBe(251);
        expect($wrestler->signature_move)->toBe('Attitude Adjustment');

        // And: Should appear in the wrestlers table
        Livewire::actingAs($admin)
            ->test(WrestlersTable::class)
            ->assertSee('John Cena')
            ->assertSee('West Newbury, MA');
    });

    test('wrestler creation with dummy data workflow', function () {
        // Given: An authenticated administrator
        $admin = administrator();

        // When: Opening create modal and using dummy data
        $component = Livewire::actingAs($admin)
            ->test(WrestlerFormModal::class)
            ->call('openModal')
            ->call('fillDummyFields');

        // Then: Form should be populated with realistic data
        expect($component->get('form.name'))->not->toBeEmpty();
        expect($component->get('form.hometown'))->not->toBeEmpty();
        expect($component->get('form.height_feet'))->toBeGreaterThanOrEqual(5);
        expect($component->get('form.height_feet'))->toBeLessThanOrEqual(7);
        expect($component->get('form.weight'))->toBeGreaterThan(150);

        // And: Can submit the dummy data successfully
        $component
            ->call('submitForm')
            ->assertHasNoErrors();

        // And: Wrestler is created with the dummy data
        $wrestlerName = $component->get('form.name');
        expect(Wrestler::where('name', $wrestlerName)->exists())->toBeTrue();
    });
});

describe('Wrestler Employment Status Management Journey', function () {
    test('complete wrestler employment lifecycle workflow', function () {
        // Given: An authenticated administrator and an unemployed wrestler
        $admin = administrator();
        $wrestler = Wrestler::factory()->create(['name' => 'Daniel Bryan']);

        // When: Employing the wrestler
        Livewire::actingAs($admin)
            ->test(WrestlersTable::class)
            ->call('handleWrestlerAction', 'employ', $wrestler->id)
            ->assertHasNoErrors();

        // Then: Wrestler should be employed
        expect($wrestler->fresh()->isEmployed())->toBeTrue();

        // When: Suspending the employed wrestler
        Livewire::actingAs($admin)
            ->test(WrestlersTable::class)
            ->call('handleWrestlerAction', 'suspend', $wrestler->id)
            ->assertHasNoErrors();

        // Then: Wrestler should be suspended
        expect($wrestler->fresh()->isSuspended())->toBeTrue();

        // When: Reinstating the suspended wrestler
        Livewire::actingAs($admin)
            ->test(WrestlersTable::class)
            ->call('handleWrestlerAction', 'reinstate', $wrestler->id)
            ->assertHasNoErrors();

        // Then: Wrestler should be employed again
        expect($wrestler->fresh()->isEmployed())->toBeTrue();
        expect($wrestler->fresh()->isSuspended())->toBeFalse();

        // When: Retiring the wrestler
        Livewire::actingAs($admin)
            ->test(WrestlersTable::class)
            ->call('handleWrestlerAction', 'retire', $wrestler->id)
            ->assertHasNoErrors();

        // Then: Wrestler should be retired
        expect($wrestler->fresh()->isRetired())->toBeTrue();

        // When: Unretiring the wrestler
        Livewire::actingAs($admin)
            ->test(WrestlersTable::class)
            ->call('handleWrestlerAction', 'unretire', $wrestler->id)
            ->assertHasNoErrors();

        // Then: Wrestler should no longer be retired
        expect($wrestler->fresh()->isRetired())->toBeFalse();

        // And: Should have employment history tracking
        expect($wrestler->fresh()->hasEmploymentHistory())->toBeTrue();
    });

    test('wrestler injury management workflow', function () {
        // Given: An employed wrestler
        $admin = administrator();
        $wrestler = Wrestler::factory()->bookable()->create(['name' => 'CM Punk']);

        // When: Injuring the wrestler
        Livewire::actingAs($admin)
            ->test(WrestlersTable::class)
            ->call('handleWrestlerAction', 'injure', $wrestler->id)
            ->assertHasNoErrors();

        // Then: Wrestler should be injured
        expect($wrestler->fresh()->isInjured())->toBeTrue();

        // When: Healing the wrestler from injury
        Livewire::actingAs($admin)
            ->test(WrestlersTable::class)
            ->call('handleWrestlerAction', 'heal', $wrestler->id)
            ->assertHasNoErrors();

        // Then: Wrestler should no longer be injured
        expect($wrestler->fresh()->isInjured())->toBeFalse();
        expect($wrestler->fresh()->isEmployed())->toBeTrue();
    });
});

describe('Wrestler Profile Management Journey', function () {
    test('administrator can edit wrestler details through UI workflow', function () {
        // Given: An existing wrestler and authenticated administrator
        $admin = administrator();
        $wrestler = Wrestler::factory()->create([
            'name' => 'Original Name',
            'hometown' => 'Original City, ST',
        ]);

        // When: Opening edit modal for the wrestler
        $component = Livewire::actingAs($admin)
            ->test(WrestlerFormModal::class)
            ->call('openModal', $wrestler->id)
            ->assertSet('isModalOpen', true);

        // Then: Form should be populated with existing data
        expect($component->get('form.name'))->toBe('Original Name');
        expect($component->get('form.hometown'))->toBe('Original City, ST');

        // When: Updating wrestler information
        $component
            ->set('form.name', 'Updated Name')
            ->set('form.hometown', 'Updated City, UT')
            ->set('form.signature_move', 'New Finisher')
            ->call('submitForm')
            ->assertHasNoErrors()
            ->assertSet('isModalOpen', false);

        // Then: Wrestler should be updated in database
        $wrestler->refresh();
        expect($wrestler->name)->toBe('Updated Name');
        expect($wrestler->hometown)->toBe('Updated City, UT');
        expect($wrestler->signature_move)->toBe('New Finisher');

        // And: Updated information should appear in table
        Livewire::actingAs($admin)
            ->test(WrestlersTable::class)
            ->assertSee('Updated Name')
            ->assertSee('Updated City, UT');
    });
});

describe('Wrestler Detail View Journey', function () {
    test('administrator can view complete wrestler profile and history', function () {
        // Given: A wrestler with some history
        $admin = administrator();
        $wrestler = Wrestler::factory()->create([
            'name' => 'Stone Cold Steve Austin',
            'hometown' => 'Austin, TX',
        ]);

        // When: Visiting the wrestler's detail page
        actingAs($admin)
            ->get(route('wrestlers.show', $wrestler))
            ->assertOk()
            ->assertSee('Austin, TX');

        // Then: Should see all the history tables
        $historyTables = [
            'previous-title-championships-table',
            'previous-matches-table',
            'previous-tag-teams-table',
            'previous-managers-table',
            'previous-stables-table',
        ];

        foreach ($historyTables as $table) {
            actingAs($admin)
                ->get(route('wrestlers.show', $wrestler))
                ->assertSeeLivewire("wrestlers.tables.{$table}");
        }
    });
});

describe('Wrestler Search and Filtering Journey', function () {
    test('administrator can search and filter wrestlers effectively', function () {
        // Given: Multiple wrestlers with different statuses
        $admin = administrator();
        $bookableWrestler = Wrestler::factory()->bookable()->create(['name' => 'John Cena']);
        $releasedWrestler = Wrestler::factory()->released()->create(['name' => 'CM Punk']);
        $retiredWrestler = Wrestler::factory()->retired()->create(['name' => 'Mick Foley']);

        // When: Searching for specific wrestler
        Livewire::actingAs($admin)
            ->test(WrestlersTable::class)
            ->set('search', 'John')
            ->assertSee($bookableWrestler->name)
            ->assertDontSee($releasedWrestler->name)
            ->assertDontSee($retiredWrestler->name);

        // When: Filtering by employment status
        Livewire::actingAs($admin)
            ->test(WrestlersTable::class)
            ->set('filterComponents.status', 'employed')
            ->assertSee($bookableWrestler->name)
            ->assertDontSee($releasedWrestler->name);

        // When: Filtering by released status
        Livewire::actingAs($admin)
            ->test(WrestlersTable::class)
            ->set('filterComponents.status', 'released')
            ->assertSee($releasedWrestler->name)
            ->assertDontSee($bookableWrestler->name);

        // When: Clearing filters
        Livewire::actingAs($admin)
            ->test(WrestlersTable::class)
            ->set('filterComponents.status', '')
            ->set('search', '')
            ->assertSee($bookableWrestler->name)
            ->assertSee($releasedWrestler->name)
            ->assertSee($retiredWrestler->name);
    });
});

describe('Wrestler Deletion and Restoration Journey', function () {
    test('administrator can delete and restore wrestlers through UI', function () {
        // Given: An existing wrestler
        $admin = administrator();
        $wrestler = Wrestler::factory()->create(['name' => 'Test Wrestler']);

        // When: Deleting the wrestler
        Livewire::actingAs($admin)
            ->test(WrestlersTable::class)
            ->call('delete', $wrestler)
            ->assertHasNoErrors();

        // Then: Wrestler should be soft deleted
        expect($wrestler->fresh()->trashed())->toBeTrue();
        expect(Wrestler::onlyTrashed()->find($wrestler->id))->not->toBeNull();

        // When: Restoring the wrestler
        Livewire::actingAs($admin)
            ->test(WrestlersTable::class)
            ->call('restore', $wrestler->id)
            ->assertHasNoErrors();

        // Then: Wrestler should be restored
        expect($wrestler->fresh())->not->toBeNull();
        expect($wrestler->fresh()->name)->toBe('Test Wrestler');
    });
});
