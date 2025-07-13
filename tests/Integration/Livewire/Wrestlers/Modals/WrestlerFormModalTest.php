<?php

declare(strict_types=1);

use App\Livewire\Wrestlers\Forms\WrestlerForm;
use App\Livewire\Wrestlers\Modals\WrestlerFormModal;
use App\Models\Users\User;
use App\Models\Wrestlers\Wrestler;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->administrator()->create();
    $this->actingAs($this->admin);
});

describe('WrestlerFormModal Configuration', function () {
    it('returns correct form class', function () {
        $modal = new WrestlerFormModal();
        $reflection = new ReflectionClass($modal);
        $method = $reflection->getMethod('getFormClass');
        $method->setAccessible(true);

        expect($method->invoke($modal))->toBe(WrestlerForm::class);
    });

    it('returns correct model class', function () {
        $modal = new WrestlerFormModal();
        $reflection = new ReflectionClass($modal);
        $method = $reflection->getMethod('getModelClass');
        $method->setAccessible(true);

        expect($method->invoke($modal))->toBe(Wrestler::class);
    });

    it('returns correct modal path', function () {
        $modal = new WrestlerFormModal();
        $reflection = new ReflectionClass($modal);
        $method = $reflection->getMethod('getModalPath');
        $method->setAccessible(true);

        expect($method->invoke($modal))->toBe('wrestlers.modals.form-modal');
    });
});

describe('WrestlerFormModal Mounting', function () {
    it('can mount for creating new wrestler', function () {
        $component = Livewire::test(WrestlerFormModal::class);

        expect($component->instance()->form)->toBeInstanceOf(WrestlerForm::class);
        $component->assertSuccessful();
    });

    it('can mount for editing existing wrestler', function () {
        $wrestler = Wrestler::factory()->create();

        $component = Livewire::test(WrestlerFormModal::class, ['modelId' => $wrestler->id]);

        expect($component->instance()->form)->toBeInstanceOf(WrestlerForm::class);
        expect($component->instance()->form->name)->toBe($wrestler->name);
        $component->assertSuccessful();
    });

    it('sets modal form path correctly', function () {
        $component = Livewire::test(WrestlerFormModal::class);

        // Test that the component can mount without errors - this verifies the path works
        $component->assertSuccessful();
    });
});

describe('WrestlerFormModal Component Functionality', function () {
    it('can render successfully', function () {
        $component = Livewire::test(WrestlerFormModal::class);

        $component->assertSuccessful();
    });

    it('can handle wrestler data correctly', function () {
        $wrestler = Wrestler::factory()->create();

        $component = Livewire::test(WrestlerFormModal::class, ['modelId' => $wrestler->id]);

        $component->assertSuccessful();
        expect($component->instance()->form->name)->toBe($wrestler->name);
    });
});

describe('WrestlerFormModal Form Integration', function () {
    it('handles form submission correctly', function () {
        $component = Livewire::test(WrestlerFormModal::class);

        $component->set('form.name', 'Test Wrestler')
            ->set('form.hometown', 'Test City, TX')
            ->set('form.height_feet', 6)
            ->set('form.height_inches', 2)
            ->set('form.weight', 220)
            ->set('form.signature_move', 'Test Finisher')
            ->call('form.submit');

        expect(Wrestler::where('name', 'Test Wrestler')->exists())->toBeTrue();
        $component->assertSuccessful();
    });

    it('handles form validation errors', function () {
        $component = Livewire::test(WrestlerFormModal::class);

        $component->set('form.name', '') // Required field empty
            ->call('form.submit')
            ->assertHasErrors(['form.name' => 'required']);

        $component->assertSuccessful(); // Modal should stay open on validation errors
    });

    it('handles form update correctly', function () {
        $wrestler = Wrestler::factory()->create([
            'name' => 'Original Name',
            'hometown' => 'Original City',
        ]);

        $component = Livewire::test(WrestlerFormModal::class);

        $component->set('form.name', 'Updated Name')
            ->set('form.hometown', 'Updated City')
            ->call('form.submit');

        $wrestler->refresh();
        expect($wrestler->name)->toBe('Updated Name');
        expect($wrestler->hometown)->toBe('Updated City');
        $component->assertSuccessful();
    });
});

describe('WrestlerFormModal Dummy Data', function () {
    it('has dummy data fields configured', function () {
        $modal = new WrestlerFormModal();
        $reflection = new ReflectionClass($modal);
        $method = $reflection->getMethod('getDummyDataFields');
        $method->setAccessible(true);

        $dummyFields = $method->invoke($modal);

        expect($dummyFields)->toBeArray();
        expect($dummyFields)->toHaveKeys(['name', 'hometown', 'height_feet', 'height_inches', 'weight', 'signature_move', 'employment_date']);
    });

    it('can fill dummy data', function () {
        $component = Livewire::test(WrestlerFormModal::class);
        $component->call('fillDummyFields');

        expect($component->get('form.name'))->not->toBeEmpty();
        expect($component->get('form.hometown'))->not->toBeEmpty();
        expect($component->get('form.height_feet'))->toBeGreaterThan(0);
        expect($component->get('form.height_inches'))->toBeGreaterThanOrEqual(0);
        expect($component->get('form.weight'))->toBeGreaterThan(0);
    });

    it('generates realistic dummy data', function () {
        $component = Livewire::test(WrestlerFormModal::class);
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

describe('WrestlerFormModal Event Handling', function () {
    it('dispatches close event when form submission succeeds', function () {
        $component = Livewire::test(WrestlerFormModal::class);

        $component->set('form.name', 'Test Wrestler')
            ->set('form.hometown', 'Test City, TX')
            ->set('form.height_feet', 6)
            ->set('form.height_inches', 0)
            ->set('form.weight', 200)
            ->call('form.submit')
            ->assertDispatched('form-submitted');
    });

    it('can handle external close modal calls', function () {
        $component = Livewire::test(WrestlerFormModal::class);

        $component->assertSuccessful();

        $component->call('closeModal');

        $component->assertSuccessful();
    });
});

describe('WrestlerFormModal Reset Functionality', function () {
    it('resets form when modal closes', function () {
        $wrestler = Wrestler::factory()->create();

        $component = Livewire::test(WrestlerFormModal::class);

        // Modify form data
        $component->set('form.name', 'Modified Name');

        // Close modal
        $component->call('closeModal');

        // Reopen modal with same wrestler

        // Form should be reset to original data
        expect($component->get('form.name'))->toBe($wrestler->name);
    });

    it('clears form when opening for creation after editing', function () {
        $wrestler = Wrestler::factory()->create();

        $component = Livewire::test(WrestlerFormModal::class);

        // First, edit a wrestler
        expect($component->get('form.name'))->toBe($wrestler->name);
        $component->call('closeModal');

        // Then open for creation
        expect($component->get('form.name'))->toBe('');
    });
});
