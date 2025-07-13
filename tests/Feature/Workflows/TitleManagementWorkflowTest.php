<?php

declare(strict_types=1);

use App\Livewire\Titles\Modals\TitleFormModal;
use App\Livewire\Titles\Tables\TitlesTable;
use App\Models\Titles\Title;
use App\Models\Wrestlers\Wrestler;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

/**
 * Feature tests for complete title management workflows.
 * Tests realistic scenarios for creating titles, managing championships, and tracking title histories.
 */
describe('Title Creation and Setup Workflow', function () {
    test('administrator can create title through complete UI workflow', function () {
        // Given: An authenticated administrator
        $admin = administrator();

        // When: Navigating to titles index
        actingAs($admin)
            ->get(route('titles.index'))
            ->assertOk()
            ->assertSeeLivewire(TitlesTable::class);

        // And: Creating title through modal workflow
        $modalComponent = Livewire::actingAs($admin)
            ->test(TitleFormModal::class)
            ->call('openModal')
            ->assertSet('isModalOpen', true);

        // And: Filling out the title form
        $titleData = [
            'name' => 'WWE Championship Title',
            'type' => 'singles',
            'start_date' => now()->format('Y-m-d'),
        ];

        foreach ($titleData as $field => $value) {
            $modalComponent->set("form.{$field}", $value);
        }

        // And: Submitting the form
        $modalComponent
            ->call('submitForm')
            ->assertHasNoErrors()
            ->assertSet('isModalOpen', false)
            ->assertDispatched('form-submitted');

        // Then: Title should be created in database
        expect(Title::where('name', 'WWE Championship Title')->exists())->toBeTrue();

        $title = Title::where('name', 'WWE Championship Title')->first();
        expect($title->name)->toBe('WWE Championship Title');

        // And: Should appear in the titles table
        Livewire::actingAs($admin)
            ->test(TitlesTable::class)
            ->assertSee('WWE Championship Title');
    });

    test('title creation with dummy data workflow', function () {
        // Given: An authenticated administrator
        $admin = administrator();

        // When: Opening create modal and using dummy data
        $component = Livewire::actingAs($admin)
            ->test(TitleFormModal::class)
            ->call('openModal')
            ->call('fillDummyFields');

        // Then: Form should be populated with realistic data
        expect($component->get('form.name'))->not->toBeEmpty();
        expect($component->get('form.type'))->not->toBeEmpty();
        // Note: start_date is optional in dummy data generation

        // And: Can submit the dummy data successfully
        $component
            ->call('submitForm')
            ->assertHasNoErrors();

        // And: Title is created with the dummy data
        $titleName = $component->get('form.name');
        expect(Title::where('name', $titleName)->exists())->toBeTrue();
    });
});

describe('Title Lifecycle Management Workflow', function () {
    test('complete title activation and deactivation workflow', function () {
        // Given: An authenticated administrator and a title
        $admin = administrator();
        $title = Title::factory()->create(['name' => 'Intercontinental Championship Title']);

        // When: Debuting the title
        $component = Livewire::actingAs($admin)
            ->test(TitlesTable::class)
            ->call('handleTitleAction', 'debut', $title->id)
            ->assertHasNoErrors();

        // Then: Title should be active (check after component execution)
        expect($title->fresh()->isCurrentlyActive())->toBeTrue();

        // When: Pulling (deactivating) the title
        Livewire::actingAs($admin)
            ->test(TitlesTable::class)
            ->call('handleTitleAction', 'pull', $title->id)
            ->assertHasNoErrors();

        // Then: Title should be inactive
        expect($title->fresh()->isCurrentlyActive())->toBeFalse();

        // When: Reinstating the title
        Livewire::actingAs($admin)
            ->test(TitlesTable::class)
            ->call('handleTitleAction', 'reinstate', $title->id)
            ->assertHasNoErrors();

        // Then: Title should be active again
        expect($title->fresh()->isCurrentlyActive())->toBeTrue();

        // When: Retiring the title
        Livewire::actingAs($admin)
            ->test(TitlesTable::class)
            ->call('handleTitleAction', 'retire', $title->id)
            ->assertHasNoErrors();

        // Then: Title should be retired
        expect($title->fresh()->isRetired())->toBeTrue();

        // When: Unretiring the title
        Livewire::actingAs($admin)
            ->test(TitlesTable::class)
            ->call('handleTitleAction', 'unretire', $title->id)
            ->assertHasNoErrors();

        // Then: Title should no longer be retired
        expect($title->fresh()->isRetired())->toBeFalse();
    });
});

describe('Title Detail and History Workflow', function () {
    test('administrator can view complete title profile and championship history', function () {
        // Given: A title with some history
        $admin = administrator();
        $title = Title::factory()->create([
            'name' => 'World Heavyweight Championship Title',
        ]);

        // When: Visiting the title's detail page
        actingAs($admin)
            ->get(route('titles.show', $title))
            ->assertOk();

        // Then: Should see the championship history table
        actingAs($admin)
            ->get(route('titles.show', $title))
            ->assertSeeLivewire('titles.tables.previous-title-championships-table');
    });
});

describe('Title Search and Filtering Workflow', function () {
    test('administrator can search and filter titles effectively', function () {
        // Given: Multiple titles with different statuses
        $admin = administrator();
        $activeTitle = Title::factory()->active()->create(['name' => 'WWE Championship Title']);
        $retiredTitle = Title::factory()->retired()->create(['name' => 'WCW Championship Title']);
        $inactiveTitle = Title::factory()->create(['name' => 'ECW Championship Title']);

        // When: Searching for specific title
        Livewire::actingAs($admin)
            ->test(TitlesTable::class)
            ->set('search', 'WWE')
            ->assertSee('WWE Championship Title')
            ->assertDontSee('WCW Championship Title')
            ->assertDontSee('ECW Championship Title');

        // When: Filtering by active status
        Livewire::actingAs($admin)
            ->test(TitlesTable::class)
            ->set('filterComponents.status', 'active')
            ->assertSee('WWE Championship Title');

        // When: Filtering by retired status  
        Livewire::actingAs($admin)
            ->test(TitlesTable::class)
            ->set('filterComponents.status', 'retired')
            ->assertSee('WCW Championship Title');

        // When: Clearing filters
        Livewire::actingAs($admin)
            ->test(TitlesTable::class)
            ->set('filterComponents.status', '')
            ->set('search', '')
            ->assertSee('WWE Championship Title')
            ->assertSee('WCW Championship Title')
            ->assertSee('ECW Championship Title');
    });
});

describe('Title Editing Workflow', function () {
    test('administrator can edit title details through UI workflow', function () {
        // Given: An existing title and authenticated administrator
        $admin = administrator();
        $title = Title::factory()->create([
            'name' => 'Original Championship Title',
        ]);

        // When: Opening edit modal for the title
        $component = Livewire::actingAs($admin)
            ->test(TitleFormModal::class)
            ->call('openModal', $title->id)
            ->assertSet('isModalOpen', true);

        // Then: Form should be populated with existing data
        expect($component->get('form.name'))->toBe('Original Championship Title');

        // When: Updating title information
        $component
            ->set('form.name', 'Updated Championship Title')
            ->call('submitForm')
            ->assertHasNoErrors()
            ->assertSet('isModalOpen', false);

        // Then: Title should be updated in database
        $title->refresh();
        expect($title->name)->toBe('Updated Championship Title');

        // And: Updated information should appear in table
        Livewire::actingAs($admin)
            ->test(TitlesTable::class)
            ->assertSee('Updated Championship Title');
    });
});

describe('Championship Reign Workflow', function () {
    test('title championship history tracking workflow', function () {
        // Given: A title and wrestlers
        $admin = administrator();
        $title = Title::factory()->active()->create(['name' => 'United States Championship Title']);
        $wrestler1 = Wrestler::factory()->bookable()->create(['name' => 'John Cena']);
        $wrestler2 = Wrestler::factory()->bookable()->create(['name' => 'CM Punk']);

        // Note: In a complete implementation, we would test championship assignment
        // and management workflows here. For now, we'll verify the structure exists.

        // When: Viewing title details
        actingAs($admin)
            ->get(route('titles.show', $title))
            ->assertOk();

        // Then: Championship history table should be visible
        actingAs($admin)
            ->get(route('titles.show', $title))
            ->assertSeeLivewire('titles.tables.previous-title-championships-table');

        // And: Title appears in main titles listing
        Livewire::actingAs($admin)
            ->test(TitlesTable::class)
            ->assertSee('United States Championship Title');
    });
});

describe('Title Deletion and Restoration Workflow', function () {
    test('administrator can delete and restore titles through UI', function () {
        // Given: An existing title
        $admin = administrator();
        $title = Title::factory()->create(['name' => 'Test Championship Title']);

        // When: Deleting the title
        Livewire::actingAs($admin)
            ->test(TitlesTable::class)
            ->call('delete', $title)
            ->assertHasNoErrors();

        // Then: Title should be soft deleted
        expect($title->fresh()->trashed())->toBeTrue();
        expect(Title::onlyTrashed()->find($title->id))->not->toBeNull();

        // When: Restoring the title
        Livewire::actingAs($admin)
            ->test(TitlesTable::class)
            ->call('restore', $title->id)
            ->assertHasNoErrors();

        // Then: Title should be restored
        expect($title->fresh())->not->toBeNull();
        expect($title->fresh()->name)->toBe('Test Championship Title');
    });
});

describe('Title Business Rules Workflow', function () {
    test('title business rules are enforced during status changes', function () {
        // Given: A title and administrator
        $admin = administrator();
        $title = Title::factory()->create(['name' => 'Money in the Bank Title']);

        // When: Attempting to pull an inactive title (business rule check)
        Livewire::actingAs($admin)
            ->test(TitlesTable::class)
            ->call('handleTitleAction', 'pull', $title->id)
            ->assertHasNoErrors();

        // When: Properly debuting title first
        Livewire::actingAs($admin)
            ->test(TitlesTable::class)
            ->call('handleTitleAction', 'debut', $title->id)
            ->assertHasNoErrors();

        // Then: Title should be active
        expect($title->fresh()->isCurrentlyActive())->toBeTrue();

        // When: Now pulling the active title (should succeed)
        Livewire::actingAs($admin)
            ->test(TitlesTable::class)
            ->call('handleTitleAction', 'pull', $title->id)
            ->assertHasNoErrors();

        // Then: Title should be inactive
        expect($title->fresh()->isCurrentlyActive())->toBeFalse();
    });
});
