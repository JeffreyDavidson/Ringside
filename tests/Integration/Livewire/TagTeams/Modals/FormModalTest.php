<?php

declare(strict_types=1);

use App\Livewire\TagTeams\Forms\CreateEditForm;
use App\Livewire\TagTeams\Modals\FormModal;
use App\Models\Managers\Manager;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

/**
 * Integration tests for TagTeams FormModal component functionality.
 *
 * INTEGRATION TEST SCOPE:
 * - Modal state management and lifecycle
 * - Form rendering and field validation
 * - Create and edit functionality with database integration
 * - Tag team-specific business rules and constraints
 * - Wrestler relationship handling and validation
 * - Manager relationship handling and synchronization
 * - Form submission and data persistence
 * - Validation error handling and display
 * - Employment date integration
 *
 * These tests verify the complete form modal workflow including
 * modal behavior, form validation, relationship management, and database operations.
 *
 * @see FormModal
 * @see Form
 */
describe('TagTeams FormModal Tests', function () {
    uses(RefreshDatabase::class);

    describe('modal rendering and state management', function () {
        test('modal opens and closes correctly', function () {
            Livewire::test(FormModal::class)
                ->assertSet('isModalOpen', false)
                ->call('openModal')
                ->assertSet('isModalOpen', true)
                ->call('closeModal')
                ->assertSet('isModalOpen', false);
        });

        test('modal renders with correct form fields', function () {
            Livewire::test(FormModal::class)
                ->call('openModal')
                ->assertPropertyWired('form.name')
                ->assertPropertyWired('form.signature_move')
                ->assertPropertyWired('form.wrestlerA')
                ->assertPropertyWired('form.wrestlerB')
                ->assertPropertyWired('form.managers')
                ->assertPropertyWired('form.employment_date');
        });

        test('modal shows correct title for create mode', function () {
            $component = Livewire::test(FormModal::class)
                ->call('openModal');

            expect($component->instance()->getModalTitle())->toBe('Add TagTeam');
        });

        test('modal shows correct title for edit mode', function () {
            $tagTeam = TagTeam::factory()->create(['name' => 'Test Tag Team']);

            $component = Livewire::test(FormModal::class)
                ->call('openModal', $tagTeam->id);

            expect($component->instance()->getModalTitle())->toContain('Edit');
            expect($component->instance()->getModalTitle())->toContain('Test Tag Team');
        });

        test('provides wrestlers list for form options', function () {
            $wrestlers = Wrestler::factory()->count(5)->create();

            $component = Livewire::test(FormModal::class)
                ->call('openModal');

            expect($component->instance()->wrestlersList)->toHaveCount(5);
            expect($component->instance()->wrestlersList->first()->id)->toBe($wrestlers->first()->id);
        });
    });

    describe('form validation rules enforcement', function () {
        test('validates required fields', function () {
            Livewire::test(FormModal::class)
                ->call('openModal')
                ->set('form.name', '')
                ->set('form.wrestlerA', null)
                ->set('form.wrestlerB', null)
                ->call('submitForm')
                ->assertHasErrors([
                    'form.name' => 'required',
                    'form.wrestlerA' => 'required',
                    'form.wrestlerB' => 'required',
                ]);
        });

        test('validates field length constraints', function () {
            Livewire::test(FormModal::class)
                ->call('openModal')
                ->set('form.name', str_repeat('A', 256))
                ->set('form.signature_move', str_repeat('C', 256))
                ->call('submitForm')
                ->assertHasErrors([
                    'form.name' => 'max',
                    'form.signature_move' => 'max',
                ]);
        });

        test('validates tag team name uniqueness', function () {
            $existingTagTeam = TagTeam::factory()->create(['name' => 'Existing Team']);

            $wrestlerA = Wrestler::factory()->create();
            $wrestlerB = Wrestler::factory()->create();

            Livewire::test(FormModal::class)
                ->call('openModal')
                ->set('form.name', 'Existing Team')
                ->set('form.wrestlerA', $wrestlerA->id)
                ->set('form.wrestlerB', $wrestlerB->id)
                ->call('submitForm')
                ->assertHasErrors(['form.name' => 'unique']);
        });

        test('validates signature move uniqueness when provided', function () {
            $existingTagTeam = TagTeam::factory()->create(['signature_move' => 'Double Slam']);

            $wrestlerA = Wrestler::factory()->create();
            $wrestlerB = Wrestler::factory()->create();

            Livewire::test(FormModal::class)
                ->call('openModal')
                ->set('form.name', 'New Team')
                ->set('form.signature_move', 'Double Slam')
                ->set('form.wrestlerA', $wrestlerA->id)
                ->set('form.wrestlerB', $wrestlerB->id)
                ->call('submitForm')
                ->assertHasErrors(['form.signature_move' => 'unique']);
        });

        test('validates wrestlers exist in database', function () {
            Livewire::test(FormModal::class)
                ->call('openModal')
                ->set('form.name', 'Test Team')
                ->set('form.wrestlerA', 9999) // Non-existent wrestler
                ->set('form.wrestlerB', 9998) // Non-existent wrestler
                ->call('submitForm')
                ->assertHasErrors([
                    'form.wrestlerA' => 'exists',
                    'form.wrestlerB' => 'exists',
                ]);
        });

        test('validates wrestlers are different', function () {
            $wrestler = Wrestler::factory()->create();

            Livewire::test(FormModal::class)
                ->call('openModal')
                ->set('form.name', 'Test Team')
                ->set('form.wrestlerA', $wrestler->id)
                ->set('form.wrestlerB', $wrestler->id) // Same wrestler
                ->call('submitForm')
                ->assertHasErrors(['form.wrestlerB' => 'different']);
        });

        test('validates managers exist when provided', function () {
            $wrestlerA = Wrestler::factory()->create();
            $wrestlerB = Wrestler::factory()->create();

            Livewire::test(FormModal::class)
                ->call('openModal')
                ->set('form.name', 'Test Team')
                ->set('form.wrestlerA', $wrestlerA->id)
                ->set('form.wrestlerB', $wrestlerB->id)
                ->set('form.managers', [9999]) // Non-existent manager
                ->call('submitForm')
                ->assertHasErrors(['form.managers.0' => 'exists']);
        });

        test('validates employment date format', function () {
            Livewire::test(FormModal::class)
                ->call('openModal')
                ->set('form.employment_date', 'invalid-date')
                ->call('submitForm')
                ->assertHasErrors(['form.employment_date' => 'date']);
        });
    });

    describe('create functionality', function () {
        test('creates new tag team with valid data', function () {
            $wrestlerA = Wrestler::factory()->create(['name' => 'Wrestler A']);
            $wrestlerB = Wrestler::factory()->create(['name' => 'Wrestler B']);
            $manager = Manager::factory()->create();

            $component = Livewire::test(FormModal::class)
                ->call('openModal')
                ->set('form.name', 'New Tag Team')
                ->set('form.signature_move', 'Team Finisher')
                ->set('form.wrestlerA', $wrestlerA->id)
                ->set('form.wrestlerB', $wrestlerB->id)
                ->set('form.managers', [$manager->id])
                ->set('form.employment_date', '2024-01-15')
                ->call('submitForm');

            $component->assertHasNoErrors();
            $component->assertSet('isModalOpen', false);

            expect(TagTeam::where('name', 'New Tag Team')->exists())->toBeTrue();

            $tagTeam = TagTeam::where('name', 'New Tag Team')->first();
            expect($tagTeam->signature_move)->toBe('Team Finisher');
            expect($tagTeam->currentWrestlers)->toHaveCount(2);
            expect($tagTeam->currentWrestlers->pluck('id')->toArray())->toContain($wrestlerA->id);
            expect($tagTeam->currentWrestlers->pluck('id')->toArray())->toContain($wrestlerB->id);
            expect($tagTeam->currentManagers)->toHaveCount(1);
            expect($tagTeam->currentManagers->first()->id)->toBe($manager->id);
        });

        test('creates tag team without optional fields', function () {
            $wrestlerA = Wrestler::factory()->create();
            $wrestlerB = Wrestler::factory()->create();

            Livewire::test(FormModal::class)
                ->call('openModal')
                ->set('form.name', 'Simple Tag Team')
                ->set('form.wrestlerA', $wrestlerA->id)
                ->set('form.wrestlerB', $wrestlerB->id)
                ->call('submitForm')
                ->assertHasNoErrors();

            $tagTeam = TagTeam::where('name', 'Simple Tag Team')->first();
            expect($tagTeam)->not()->toBeNull();
            expect($tagTeam->signature_move)->toBeNull();
            expect($tagTeam->currentManagers)->toHaveCount(0);
            expect($tagTeam->firstEmployment)->toBeNull();
        });

        test('dispatches form-submitted event on successful creation', function () {
            $wrestlerA = Wrestler::factory()->create();
            $wrestlerB = Wrestler::factory()->create();

            Livewire::test(FormModal::class)
                ->call('openModal')
                ->set('form.name', 'Event Test Team')
                ->set('form.wrestlerA', $wrestlerA->id)
                ->set('form.wrestlerB', $wrestlerB->id)
                ->call('submitForm')
                ->assertDispatched('form-submitted');
        });
    });

    describe('edit functionality', function () {
        test('loads existing tag team data for editing', function () {
            $wrestlerA = Wrestler::factory()->create(['name' => 'First Wrestler']);
            $wrestlerB = Wrestler::factory()->create(['name' => 'Second Wrestler']);
            $manager = Manager::factory()->create();

            $tagTeam = TagTeam::factory()->create([
                'name' => 'Edit Test Team',
                'signature_move' => 'Original Move',
            ]);

            // Set up relationships
            $tagTeam->wrestlers()->sync([$wrestlerA->id, $wrestlerB->id]);
            $tagTeam->managers()->sync([$manager->id]);

            $component = Livewire::test(FormModal::class)
                ->call('openModal', $tagTeam->id);

            $component->assertSet('form.name', 'Edit Test Team')
                ->assertSet('form.signature_move', 'Original Move')
                ->assertSet('form.wrestlerA', $wrestlerA->id)
                ->assertSet('form.wrestlerB', $wrestlerB->id);

            expect($component->get('form.managers'))->toContain($manager->id);
        });

        test('updates existing tag team with valid changes', function () {
            $originalWrestlerA = Wrestler::factory()->create();
            $originalWrestlerB = Wrestler::factory()->create();
            $newWrestlerA = Wrestler::factory()->create();
            $newWrestlerB = Wrestler::factory()->create();
            $manager = Manager::factory()->create();

            $tagTeam = TagTeam::factory()->create([
                'name' => 'Original Team Name',
                'signature_move' => 'Original Move',
            ]);

            $tagTeam->wrestlers()->sync([$originalWrestlerA->id, $originalWrestlerB->id]);

            Livewire::test(FormModal::class)
                ->call('openModal', $tagTeam->id)
                ->set('form.name', 'Updated Team Name')
                ->set('form.signature_move', 'New Team Finisher')
                ->set('form.wrestlerA', $newWrestlerA->id)
                ->set('form.wrestlerB', $newWrestlerB->id)
                ->set('form.managers', [$manager->id])
                ->call('submitForm')
                ->assertHasNoErrors();

            $tagTeam->refresh();
            expect($tagTeam->name)->toBe('Updated Team Name');
            expect($tagTeam->signature_move)->toBe('New Team Finisher');
            expect($tagTeam->currentWrestlers->pluck('id')->toArray())->toEqual([$newWrestlerA->id, $newWrestlerB->id]);
            expect($tagTeam->currentManagers->first()->id)->toBe($manager->id);
        });

        test('allows name uniqueness bypass for same tag team', function () {
            $wrestler1 = Wrestler::factory()->create();
            $wrestler2 = Wrestler::factory()->create();
            $tagTeam = TagTeam::factory()->create(['name' => 'Unique Team Name']);

            Livewire::test(FormModal::class)
                ->call('openModal', $tagTeam->id)
                ->set('form.name', 'Unique Team Name') // Same name should be allowed
                ->set('form.wrestlerA', $wrestler1->id)
                ->set('form.wrestlerB', $wrestler2->id)
                ->call('submitForm')
                ->assertHasNoErrors();
        });

        test('handles employment date loading from existing employment', function () {
            $tagTeam = TagTeam::factory()
                ->hasEmployments(1, ['started_at' => '2023-06-15'])
                ->create();

            $component = Livewire::test(FormModal::class)
                ->call('openModal', $tagTeam->id);

            $component->assertSet('form.employment_date', '2023-06-15');
        });
    });

    describe('wrestler relationship management', function () {
        test('synchronizes wrestler relationships on create', function () {
            $wrestlerA = Wrestler::factory()->create();
            $wrestlerB = Wrestler::factory()->create();

            Livewire::test(FormModal::class)
                ->call('openModal')
                ->set('form.name', 'Relationship Test Team')
                ->set('form.wrestlerA', $wrestlerA->id)
                ->set('form.wrestlerB', $wrestlerB->id)
                ->call('submitForm')
                ->assertHasNoErrors();

            $tagTeam = TagTeam::where('name', 'Relationship Test Team')->first();
            expect($tagTeam->wrestlers)->toHaveCount(2);
            expect($tagTeam->wrestlers->pluck('id')->sort()->values()->toArray())
                ->toEqual(collect([$wrestlerA->id, $wrestlerB->id])->sort()->values()->toArray());
        });

        test('updates wrestler relationships on edit', function () {
            $originalWrestlerA = Wrestler::factory()->create();
            $originalWrestlerB = Wrestler::factory()->create();
            $newWrestlerA = Wrestler::factory()->create();
            $newWrestlerB = Wrestler::factory()->create();

            $tagTeam = TagTeam::factory()->create(['name' => 'Update Test Team']);
            $tagTeam->wrestlers()->sync([$originalWrestlerA->id, $originalWrestlerB->id]);

            Livewire::test(FormModal::class)
                ->call('openModal', $tagTeam->id)
                ->set('form.wrestlerA', $newWrestlerA->id)
                ->set('form.wrestlerB', $newWrestlerB->id)
                ->call('submitForm')
                ->assertHasNoErrors();

            $tagTeam->refresh();
            expect($tagTeam->wrestlers->pluck('id')->sort()->values()->toArray())
                ->toEqual(collect([$newWrestlerA->id, $newWrestlerB->id])->sort()->values()->toArray());
        });

        test('removes previous wrestler relationships when updating', function () {
            $originalWrestlerA = Wrestler::factory()->create();
            $originalWrestlerB = Wrestler::factory()->create();
            $newWrestlerA = Wrestler::factory()->create();
            $newWrestlerB = Wrestler::factory()->create();

            $tagTeam = TagTeam::factory()->create();
            $tagTeam->wrestlers()->sync([$originalWrestlerA->id, $originalWrestlerB->id]);

            Livewire::test(FormModal::class)
                ->call('openModal', $tagTeam->id)
                ->set('form.wrestlerA', $newWrestlerA->id)
                ->set('form.wrestlerB', $newWrestlerB->id)
                ->call('submitForm');

            $tagTeam->refresh();
            expect($tagTeam->wrestlers->pluck('id')->toArray())->not->toContain($originalWrestlerA->id);
            expect($tagTeam->wrestlers->pluck('id')->toArray())->not->toContain($originalWrestlerB->id);
        });
    });

    describe('manager relationship management', function () {
        test('synchronizes manager relationships on create', function () {
            $wrestlerA = Wrestler::factory()->create();
            $wrestlerB = Wrestler::factory()->create();
            $manager1 = Manager::factory()->create();
            $manager2 = Manager::factory()->create();

            Livewire::test(FormModal::class)
                ->call('openModal')
                ->set('form.name', 'Manager Test Team')
                ->set('form.wrestlerA', $wrestlerA->id)
                ->set('form.wrestlerB', $wrestlerB->id)
                ->set('form.managers', [$manager1->id, $manager2->id])
                ->call('submitForm')
                ->assertHasNoErrors();

            $tagTeam = TagTeam::where('name', 'Manager Test Team')->first();
            expect($tagTeam->managers)->toHaveCount(2);
            expect($tagTeam->managers->pluck('id')->sort()->values()->toArray())
                ->toEqual(collect([$manager1->id, $manager2->id])->sort()->values()->toArray());
        });

        test('updates manager relationships on edit', function () {
            $wrestlerA = Wrestler::factory()->create();
            $wrestlerB = Wrestler::factory()->create();
            $originalManager = Manager::factory()->create();
            $newManager = Manager::factory()->create();

            $tagTeam = TagTeam::factory()->create();
            $tagTeam->wrestlers()->sync([$wrestlerA->id, $wrestlerB->id]);
            $tagTeam->managers()->sync([$originalManager->id]);

            Livewire::test(FormModal::class)
                ->call('openModal', $tagTeam->id)
                ->set('form.managers', [$newManager->id])
                ->call('submitForm')
                ->assertHasNoErrors();

            $tagTeam->refresh();
            expect($tagTeam->managers->pluck('id')->toArray())->toEqual([$newManager->id]);
            expect($tagTeam->managers->pluck('id')->toArray())->not->toContain($originalManager->id);
        });

        test('handles empty manager array correctly', function () {
            $wrestlerA = Wrestler::factory()->create();
            $wrestlerB = Wrestler::factory()->create();
            $manager = Manager::factory()->create();

            $tagTeam = TagTeam::factory()->create();
            $tagTeam->wrestlers()->sync([$wrestlerA->id, $wrestlerB->id]);
            $tagTeam->managers()->sync([$manager->id]);

            Livewire::test(FormModal::class)
                ->call('openModal', $tagTeam->id)
                ->set('form.managers', []) // Remove all managers
                ->call('submitForm')
                ->assertHasNoErrors();

            $tagTeam->refresh();
            expect($tagTeam->managers)->toHaveCount(0);
        });
    });

    describe('form submission and error handling', function () {
        test('prevents submission with validation errors', function () {
            $component = Livewire::test(FormModal::class)
                ->call('openModal')
                ->set('form.name', '') // Invalid: required
                ->call('submitForm');

            $component->assertHasErrors();
            $component->assertSet('isModalOpen', true); // Modal stays open on errors
            expect(TagTeam::count())->toBe(0); // No tag team created
        });

        test('closes modal on successful form submission', function () {
            $wrestlerA = Wrestler::factory()->create();
            $wrestlerB = Wrestler::factory()->create();

            Livewire::test(FormModal::class)
                ->call('openModal')
                ->set('form.name', 'Success Test Team')
                ->set('form.wrestlerA', $wrestlerA->id)
                ->set('form.wrestlerB', $wrestlerB->id)
                ->call('submitForm')
                ->assertSet('isModalOpen', false);
        });

        test('maintains form state on validation errors', function () {
            $wrestlerA = Wrestler::factory()->create();

            $component = Livewire::test(FormModal::class)
                ->call('openModal')
                ->set('form.name', '') // Will cause error
                ->set('form.signature_move', 'Valid Move')
                ->set('form.wrestlerA', $wrestlerA->id)
                ->call('submitForm');

            // Valid fields should be preserved
            $component->assertSet('form.signature_move', 'Valid Move')
                ->assertSet('form.wrestlerA', $wrestlerA->id);
        });
    });

    describe('dummy data functionality', function () {
        test('can fill dummy fields for development workflow', function () {
            // Create wrestlers for dummy data to use
            Wrestler::factory()->count(5)->create();

            $component = Livewire::test(FormModal::class)
                ->call('openModal')
                ->call('fillDummyFields');

            // All required fields should be populated
            expect($component->get('form.name'))->not->toBeEmpty();
            expect($component->get('form.wrestlerA'))->not()->toBeNull();
            expect($component->get('form.wrestlerB'))->not()->toBeNull();
        });

        test('dummy data uses different wrestlers', function () {
            // Create enough wrestlers for dummy data
            Wrestler::factory()->count(5)->create();

            $component = Livewire::test(FormModal::class)
                ->call('openModal')
                ->call('fillDummyFields');

            $wrestlerA = $component->get('form.wrestlerA');
            $wrestlerB = $component->get('form.wrestlerB');

            expect($wrestlerA)->not->toBe($wrestlerB);
        });
    });

    describe('integration with employment system', function () {
        test('creates employment record when employment date provided', function () {
            $wrestlerA = Wrestler::factory()->create();
            $wrestlerB = Wrestler::factory()->create();

            Livewire::test(FormModal::class)
                ->call('openModal')
                ->set('form.name', 'Employment Test Team')
                ->set('form.wrestlerA', $wrestlerA->id)
                ->set('form.wrestlerB', $wrestlerB->id)
                ->set('form.employment_date', '2024-01-01')
                ->call('submitForm')
                ->assertHasNoErrors();

            $tagTeam = TagTeam::where('name', 'Employment Test Team')->first();
            expect($tagTeam->firstEmployment)->not()->toBeNull();
            expect($tagTeam->firstEmployment->started_at->toDateString())->toBe('2024-01-01');
        });

        test('does not create employment record when date not provided', function () {
            $wrestlerA = Wrestler::factory()->create();
            $wrestlerB = Wrestler::factory()->create();

            Livewire::test(FormModal::class)
                ->call('openModal')
                ->set('form.name', 'No Employment Test Team')
                ->set('form.wrestlerA', $wrestlerA->id)
                ->set('form.wrestlerB', $wrestlerB->id)
                ->call('submitForm')
                ->assertHasNoErrors();

            $tagTeam = TagTeam::where('name', 'No Employment Test Team')->first();
            expect($tagTeam->firstEmployment)->toBeNull();
        });
    });
});
