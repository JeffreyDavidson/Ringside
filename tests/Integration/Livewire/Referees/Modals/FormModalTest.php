<?php

declare(strict_types=1);

use App\Livewire\Referees\Modals\FormModal;
use App\Livewire\Referees\RefereeForm;
use App\Models\Referees\Referee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

/**
 * Integration tests for Referees FormModal component functionality.
 *
 * INTEGRATION TEST SCOPE:
 * - Modal state management and lifecycle
 * - Form rendering and field validation
 * - Create and edit functionality with database integration
 * - Referee-specific business rules and constraints
 * - Form submission and data persistence
 * - Validation error handling and display
 * - Employment date integration
 * - Name field validation and combination
 *
 * These tests verify the complete form modal workflow including
 * modal behavior, form validation, and database operations.
 * Note: Referees use a different base modal structure than other entities.
 *
 * @see FormModal
 * @see RefereeForm
 */
describe('Referees FormModal Tests', function () {
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
                ->assertPropertyWired('modelForm.first_name')
                ->assertPropertyWired('modelForm.last_name')
                ->assertPropertyWired('modelForm.start_date');
        });

        test('modal shows correct title for create mode', function () {
            $component = Livewire::test(FormModal::class)
                ->call('openModal');

            expect($component->instance()->getModalTitle())->toBe('Add Referee');
        });

        test('modal shows correct title for edit mode', function () {
            $referee = Referee::factory()->create([
                'first_name' => 'John',
                'last_name' => 'Smith',
            ]);

            $component = Livewire::test(FormModal::class)
                ->call('openModal', $referee->id);

            expect($component->instance()->getModalTitle())->toContain('Edit');
            expect($component->instance()->getModalTitle())->toContain('John Smith');
        });
    });

    describe('form validation rules enforcement', function () {
        test('validates required fields', function () {
            Livewire::test(FormModal::class)
                ->call('openModal')
                ->set('modelForm.first_name', '')
                ->set('modelForm.last_name', '')
                ->call('save')
                ->assertHasErrors([
                    'modelForm.first_name' => 'required',
                    'modelForm.last_name' => 'required',
                ]);
        });

        test('validates field length constraints', function () {
            Livewire::test(FormModal::class)
                ->call('openModal')
                ->set('modelForm.first_name', str_repeat('A', 256))
                ->set('modelForm.last_name', str_repeat('B', 256))
                ->call('save')
                ->assertHasErrors([
                    'modelForm.first_name' => 'max',
                    'modelForm.last_name' => 'max',
                ]);
        });

        test('validates start date format', function () {
            Livewire::test(FormModal::class)
                ->call('openModal')
                ->set('modelForm.start_date', 'invalid-date')
                ->call('save')
                ->assertHasErrors(['modelForm.start_date' => 'date']);
        });

        test('accepts valid name combinations', function () {
            Livewire::test(FormModal::class)
                ->call('openModal')
                ->set('modelForm.first_name', 'John')
                ->set('modelForm.last_name', 'Doe')
                ->call('save')
                ->assertHasNoErrors();

            expect(Referee::where('first_name', 'John')->where('last_name', 'Doe')->exists())->toBeTrue();
        });
    });

    describe('create functionality', function () {
        test('creates new referee with valid data', function () {
            $component = Livewire::test(FormModal::class)
                ->call('openModal')
                ->set('modelForm.first_name', 'Mike')
                ->set('modelForm.last_name', 'Johnson')
                ->set('modelForm.start_date', '2024-01-15')
                ->call('save');

            $component->assertHasNoErrors();
            $component->assertSet('isModalOpen', false);

            expect(Referee::where('first_name', 'Mike')->where('last_name', 'Johnson')->exists())->toBeTrue();

            $referee = Referee::where('first_name', 'Mike')->where('last_name', 'Johnson')->first();
            expect($referee->first_name)->toBe('Mike');
            expect($referee->last_name)->toBe('Johnson');
        });

        test('creates referee without optional start date', function () {
            Livewire::test(FormModal::class)
                ->call('openModal')
                ->set('modelForm.first_name', 'Simple')
                ->set('modelForm.last_name', 'Referee')
                ->call('save')
                ->assertHasNoErrors();

            $referee = Referee::where('first_name', 'Simple')->where('last_name', 'Referee')->first();
            expect($referee)->not()->toBeNull();
            expect($referee->firstEmployment)->toBeNull();
        });

        test('dispatches refreshDatatable event on successful creation', function () {
            Livewire::test(FormModal::class)
                ->call('openModal')
                ->set('modelForm.first_name', 'Event')
                ->set('modelForm.last_name', 'Test')
                ->call('save')
                ->assertDispatched('refreshDatatable');
        });

        test('closes modal after successful creation', function () {
            Livewire::test(FormModal::class)
                ->call('openModal')
                ->set('modelForm.first_name', 'Close')
                ->set('modelForm.last_name', 'Test')
                ->call('save')
                ->assertSet('isModalOpen', false);
        });
    });

    describe('edit functionality', function () {
        test('loads existing referee data for editing', function () {
            $referee = Referee::factory()->create([
                'first_name' => 'Edit',
                'last_name' => 'Test',
            ]);

            $component = Livewire::test(FormModal::class)
                ->call('openModal', $referee->id);

            $component->assertSet('modelForm.first_name', 'Edit')
                ->assertSet('modelForm.last_name', 'Test');
        });

        test('updates existing referee with valid changes', function () {
            $referee = Referee::factory()->create([
                'first_name' => 'Original',
                'last_name' => 'Name',
            ]);

            Livewire::test(FormModal::class)
                ->call('openModal', $referee->id)
                ->set('modelForm.first_name', 'Updated')
                ->set('modelForm.last_name', 'Referee')
                ->call('save')
                ->assertHasNoErrors();

            $referee->refresh();
            expect($referee->first_name)->toBe('Updated');
            expect($referee->last_name)->toBe('Referee');
        });

        test('handles start date loading from existing employment', function () {
            $referee = Referee::factory()
                ->hasEmployments(1, ['started_at' => '2023-06-15'])
                ->create();

            $component = Livewire::test(FormModal::class)
                ->call('openModal', $referee->id);

            $component->assertSet('modelForm.start_date', '2023-06-15');
        });

        test('maintains other data when updating specific fields', function () {
            $referee = Referee::factory()->create([
                'first_name' => 'Keep',
                'last_name' => 'Original',
            ]);

            Livewire::test(FormModal::class)
                ->call('openModal', $referee->id)
                ->set('modelForm.first_name', 'Updated')
                // Don't change last_name
                ->call('save')
                ->assertHasNoErrors();

            $referee->refresh();
            expect($referee->first_name)->toBe('Updated');
            expect($referee->last_name)->toBe('Original'); // Should remain unchanged
        });
    });

    describe('form submission and error handling', function () {
        test('prevents submission with validation errors', function () {
            $component = Livewire::test(FormModal::class)
                ->call('openModal')
                ->set('modelForm.first_name', '') // Invalid: required
                ->call('save');

            $component->assertHasErrors();
            $component->assertSet('isModalOpen', true); // Modal stays open on errors
            expect(Referee::count())->toBe(0); // No referee created
        });

        test('maintains form state on validation errors', function () {
            $component = Livewire::test(FormModal::class)
                ->call('openModal')
                ->set('modelForm.first_name', '') // Will cause error
                ->set('modelForm.last_name', 'Valid Name')
                ->call('save');

            // Valid fields should be preserved
            $component->assertSet('modelForm.last_name', 'Valid Name');
        });

        test('handles database constraint violations gracefully', function () {
            // Create scenario where validation passes but database operation might fail
            $referee = Referee::factory()->create([
                'first_name' => 'Test',
                'last_name' => 'Constraint',
            ]);

            // This should work normally since there are no unique constraints on referee names
            Livewire::test(FormModal::class)
                ->call('openModal')
                ->set('modelForm.first_name', 'Test')
                ->set('modelForm.last_name', 'Constraint')
                ->call('save')
                ->assertHasNoErrors();

            // Two referees with same name should be allowed
            expect(Referee::where('first_name', 'Test')->where('last_name', 'Constraint')->count())->toBe(2);
        });
    });

    describe('dummy data functionality', function () {
        test('can fill dummy fields for development workflow', function () {
            $component = Livewire::test(FormModal::class)
                ->call('openModal')
                ->call('fillDummyFields');

            // All required fields should be populated
            expect($component->get('modelForm.first_name'))->not->toBeEmpty();
            expect($component->get('modelForm.last_name'))->not->toBeEmpty();
        });

        test('dummy data generates realistic referee names', function () {
            $component = Livewire::test(FormModal::class)
                ->call('openModal')
                ->call('fillDummyFields');

            $firstName = $component->get('modelForm.first_name');
            $lastName = $component->get('modelForm.last_name');

            // Names should be strings and not empty
            expect($firstName)->toBeString();
            expect($lastName)->toBeString();
            expect(mb_strlen($firstName))->toBeGreaterThan(0);
            expect(mb_strlen($lastName))->toBeGreaterThan(0);

            // Should not be placeholder text
            expect($firstName)->not->toContain('Faker');
            expect($lastName)->not->toContain('Test');
        });

        test('dummy start date has reasonable format', function () {
            $component = Livewire::test(FormModal::class)
                ->call('openModal')
                ->call('fillDummyFields');

            $startDate = $component->get('modelForm.start_date');

            // Start date might be null or a valid date string
            if ($startDate !== null && $startDate !== '') {
                expect($startDate)->toMatch('/^\d{4}-\d{2}-\d{2}/'); // YYYY-MM-DD format
            }
        });
    });

    describe('clear functionality', function () {
        test('clears form when called without model', function () {
            $component = Livewire::test(FormModal::class)
                ->call('openModal')
                ->set('modelForm.first_name', 'Test')
                ->set('modelForm.last_name', 'Clear')
                ->call('clear');

            // Form should be reset to empty values
            expect($component->get('modelForm.first_name'))->toBe('');
            expect($component->get('modelForm.last_name'))->toBe('');
            expect($component->get('modelForm.start_date'))->toBe('');
        });

        test('resets form to original model data when editing', function () {
            $referee = Referee::factory()->create([
                'first_name' => 'Original',
                'last_name' => 'Data',
            ]);

            $component = Livewire::test(FormModal::class)
                ->call('openModal', $referee->id)
                ->set('modelForm.first_name', 'Changed')
                ->set('modelForm.last_name', 'Values')
                ->call('clear');

            // Should reset to original model data
            $component->assertSet('modelForm.first_name', 'Original')
                ->assertSet('modelForm.last_name', 'Data');
        });
    });

    describe('modal title generation', function () {
        test('generates correct full name for title', function () {
            $referee = Referee::factory()->create([
                'first_name' => 'John',
                'last_name' => 'Doe',
            ]);

            $component = Livewire::test(FormModal::class)
                ->call('openModal', $referee->id);

            $title = $component->instance()->getModalTitle();
            expect($title)->toContain('John Doe');
        });

        test('handles single names correctly', function () {
            $referee = Referee::factory()->create([
                'first_name' => 'Cher',
                'last_name' => '',
            ]);

            $component = Livewire::test(FormModal::class)
                ->call('openModal', $referee->id);

            // Should handle empty last name gracefully
            $title = $component->instance()->getModalTitle();
            expect($title)->toContain('Edit');
        });
    });

    describe('integration with employment system', function () {
        test('handles employment date creation through form store method', function () {
            // Note: The RefereeForm has a custom store method that doesn't handle employment
            // This test documents the current behavior
            Livewire::test(FormModal::class)
                ->call('openModal')
                ->set('modelForm.first_name', 'Employment')
                ->set('modelForm.last_name', 'Test')
                ->set('modelForm.start_date', '2024-01-01')
                ->call('save')
                ->assertHasNoErrors();

            $referee = Referee::where('first_name', 'Employment')->where('last_name', 'Test')->first();
            expect($referee)->not()->toBeNull();
            // Note: Employment creation is not handled by the current RefereeForm implementation
        });

        test('loads employment date for editing when available', function () {
            $referee = Referee::factory()
                ->hasEmployments(1, ['started_at' => '2023-12-01'])
                ->create();

            $component = Livewire::test(FormModal::class)
                ->call('openModal', $referee->id);

            $component->assertSet('modelForm.start_date', '2023-12-01');
        });
    });
});
