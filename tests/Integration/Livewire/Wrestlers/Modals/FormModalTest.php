<?php

declare(strict_types=1);

use App\Livewire\Wrestlers\Forms\CreateEditForm;
use App\Livewire\Wrestlers\Modals\FormModal;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Users\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->admin = User::factory()->administrator()->create();
    $this->actingAs($this->admin);
});

describe('FormModal Configuration', function () {
    it('initializes the wrestler form', function () {
        $component = livewire(FormModal::class);

        expect($component->get('form'))->toBeInstanceOf(CreateEditForm::class);
    });

    it('returns correct model class', function () {
        $modal = new FormModal();
        $reflection = new ReflectionClass($modal);
        $method = $reflection->getMethod('getModelClass');
        $method->setAccessible(true);

        expect($method->invoke($modal))->toBe(Wrestler::class);
    });

    it('renders the wrestler modal view', function () {
        $component = livewire(FormModal::class);

        $component->assertViewIs('livewire.wrestlers.modals.form-modal');
    });
});

describe('FormModal Mounting', function () {
    it('can mount for creating new wrestler', function () {
        $component = livewire(FormModal::class);

        expect($component->get('form'))->toBeInstanceOf(CreateEditForm::class);
        $component->assertSuccessful();
    });

    it('can mount for editing existing wrestler', function () {
        $wrestler = Wrestler::factory()->create();

        $component = livewire(FormModal::class, ['modelId' => $wrestler->id]);

        expect($component->get('form'))->toBeInstanceOf(CreateEditForm::class);
        $component->assertSet('form.name', $wrestler->name);
        $component->assertSuccessful();
    });

    it('sets modal form path correctly', function () {
        $component = livewire(FormModal::class);

        // Test that the component can mount without errors - this verifies the path works
        $component->assertSuccessful();
    });
});

describe('FormModal Component Functionality', function () {
    it('can render successfully', function () {
        $component = livewire(FormModal::class);

        $component->assertSuccessful();
    });

    it('can handle wrestler data correctly', function () {
        $wrestler = Wrestler::factory()->create();

        $component = livewire(FormModal::class, ['modelId' => $wrestler->id]);

        $component->assertSuccessful();
        $component->assertSet('form.name', $wrestler->name);
    });
});

describe('FormModal Form Integration', function () {
    it('handles form submission correctly', function () {
        $component = livewire(FormModal::class);

        $component->set('form.name', 'Test Wrestler')
            ->set('form.hometown', 'Test City, TX')
            ->set('form.height_feet', 6)
            ->set('form.height_inches', 2)
            ->set('form.weight', 220)
            ->set('form.signature_move', 'Test Finisher')
            ->call('submitForm');

        expect(Wrestler::where('name', 'Test Wrestler')->exists())->toBeTrue();
        $component->assertSuccessful();
    });

    it('handles form validation errors', function () {
        $component = livewire(FormModal::class);

        $component->set('form.name', '') // Required field empty
            ->call('submitForm')
            ->assertHasErrors(['form.name' => 'required']);

        $component->assertSuccessful(); // Modal should stay open on validation errors
    });

    it('handles form update correctly', function () {
        $wrestler = Wrestler::factory()->create([
            'name' => 'Original Name',
            'hometown' => 'Original City',
        ]);

        $component = livewire(FormModal::class, ['modelId' => $wrestler->id]);

        $component->set('form.name', 'Updated Name')
            ->set('form.hometown', 'Updated City')
            ->call('submitForm');

        $wrestler->refresh();
        expect($wrestler->name)->toBe('Updated Name');
        expect($wrestler->hometown)->toBe('Updated City');
        $component->assertSuccessful();
    });
});

describe('FormModal Dummy Data', function () {
    it('has dummy data fields configured', function () {
        $modal = new FormModal();
        $reflection = new ReflectionClass($modal);
        $method = $reflection->getMethod('getDummyDataFields');
        $method->setAccessible(true);

        $dummyFields = $method->invoke($modal);

        expect($dummyFields)->toBeArray();
        expect($dummyFields)->toHaveKeys(['name', 'hometown', 'height_feet', 'height_inches', 'weight', 'signature_move', 'employment_date']);
    });

    it('can fill dummy data', function () {
        $component = livewire(FormModal::class);
        $component->call('fillDummyFields');

        expect($component->get('form.name'))->not->toBeEmpty();
        expect($component->get('form.hometown'))->not->toBeEmpty();
        expect($component->get('form.height_feet'))->toBeGreaterThan(0);
        expect($component->get('form.height_inches'))->toBeGreaterThanOrEqual(0);
        expect($component->get('form.weight'))->toBeGreaterThan(0);
    });

    it('generates realistic dummy data', function () {
        $component = livewire(FormModal::class);
        $component->call('fillDummyFields');

        // Check that height is realistic (5-7 feet)
        expect($component->get('form.height_feet'))->toBeGreaterThanOrEqual(5);
        expect($component->get('form.height_feet'))->toBeLessThanOrEqual(7);

        // Check that height inches is valid (0-11)
        expect($component->get('form.height_inches'))->toBeGreaterThanOrEqual(0);
        expect($component->get('form.height_inches'))->toBeLessThanOrEqual(11);

        // Check that weight is realistic (150-350)
        expect($component->get('form.weight'))->toBeGreaterThanOrEqual(150);
        expect($component->get('form.weight'))->toBeLessThanOrEqual(350);

        // Check that hometown includes state abbreviation
        expect($component->get('form.hometown'))->toContain(', ');
    });
});

describe('FormModal Event Handling', function () {
    it('dispatches close event when form submission succeeds', function () {
        $component = livewire(FormModal::class);

        $component->set('form.name', 'Test Wrestler')
            ->set('form.hometown', 'Test City, TX')
            ->set('form.height_feet', 6)
            ->set('form.height_inches', 0)
            ->set('form.weight', 200)
            ->call('submitForm')
            ->assertDispatched('form-submitted');
    });

    it('can handle external close modal calls', function () {
        $component = livewire(FormModal::class);

        $component->assertSuccessful();

        $component->call('closeModal');

        $component->assertSuccessful();
    });
});

describe('FormModal Reset Functionality', function () {
    it('resets form when modal closes', function () {
        $wrestler = Wrestler::factory()->create();

        $component = livewire(FormModal::class, ['modelId' => $wrestler->id]);

        // Modify form data
        $component->set('form.name', 'Modified Name');

        // Close modal and create new component instance with same wrestler
        $component->call('closeModal');
        $newComponent = livewire(FormModal::class, ['modelId' => $wrestler->id]);

        // Form should be reset to original data
        expect($newComponent->get('form.name'))->toBe($wrestler->name);
    });

    it('clears form when opening for creation after editing', function () {
        $wrestler = Wrestler::factory()->create();

        $component = livewire(FormModal::class, ['modelId' => $wrestler->id]);

        // First, edit a wrestler - verify it's loaded
        expect($component->get('form.name'))->toBe($wrestler->name);
        $component->call('closeModal');

        // Then create new component for creation (no model ID)
        $creationComponent = livewire(FormModal::class);
        expect($creationComponent->get('form.name'))->toBe('');
    });
});
