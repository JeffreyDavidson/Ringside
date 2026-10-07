<?php

declare(strict_types=1);

use App\Actions\Wrestlers\UpdateAction;
use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Roster\Individuals\CannotBeEmployedException;
use App\Livewire\Wrestlers\Forms\CreateEditForm;
use App\Livewire\Wrestlers\Modals\FormModal;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Date;
use JMac\Testing\Double;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->admin = administrator();
    actingAs($this->admin);
});

describe('FormModal Configuration', function () {
    it('initializes the wrestler form', function () {
        $component = livewire(FormModal::class);

        expect($component->get('form'))->toBeInstanceOf(CreateEditForm::class);
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
        $component->set('form.name', $wrestler->name);

        expect($component->get('form'))->toBeInstanceOf(CreateEditForm::class);
        $component->assertSet('form.name', $wrestler->name);
        $component
            ->assertSuccessful()
            ->assertSee("Edit {$wrestler->name}");
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
    it('binds the employment date input to the form property', function () {
        livewire(FormModal::class)
            ->assertSeeHtml('wire:model="form.employment_date"')
            ->assertDontSeeHtml('wire:model="form.start_date"');
    });

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

    it('explains the allowed height and weight ranges in validation messages', function (
        int $feet,
        int $inches,
        int $weight,
        array $errors,
    ) {
        livewire(FormModal::class)
            ->set('form.name', 'Test Wrestler')
            ->set('form.hometown', 'Test City, TX')
            ->set('form.height_feet', $feet)
            ->set('form.height_inches', $inches)
            ->set('form.weight', $weight)
            ->call('submitForm')
            ->assertHasErrors($errors);
    })->with([
        'too tall' => [8, 12, 220, [
            'form.height_feet' => 'Enter the feet as a whole number from 0 to 7.',
            'form.height_inches' => 'Enter the inches as a whole number from 0 to 11.',
        ]],
        'negative' => [-1, -1, 220, [
            'form.height_feet' => 'Enter the feet as a whole number from 0 to 7.',
            'form.height_inches' => 'Enter a height of at least 1 inch, with the inches from 0 to 11.',
        ]],
        'no height' => [0, 0, 220, [
            'form.height_inches' => 'Enter a height of at least 1 inch, with the inches from 0 to 11.',
        ]],
        'two digit weight' => [6, 2, 95, [
            'form.weight' => 'Enter the weight in pounds as a 3-digit number, from 100 to 999.',
        ]],
        'four digit weight' => [6, 2, 1000, [
            'form.weight' => 'Enter the weight in pounds as a 3-digit number, from 100 to 999.',
        ]],
    ]);

    it('rejects a zero height with a field error instead of failing', function () {
        livewire(FormModal::class)
            ->set('form.name', 'Zero Height Wrestler')
            ->set('form.hometown', 'Test City, TX')
            ->set('form.height_feet', 0)
            ->set('form.height_inches', 0)
            ->set('form.weight', 220)
            ->call('submitForm')
            ->assertHasErrors(['form.height_inches' => 'min'])
            ->assertSuccessful();

        expect(Wrestler::where('name', 'Zero Height Wrestler')->exists())->toBeFalse();
    });

    it('rejects negative heights', function (string $field) {
        livewire(FormModal::class)
            ->set('form.name', 'Negative Height Wrestler')
            ->set('form.hometown', 'Test City, TX')
            ->set('form.height_feet', 6)
            ->set('form.height_inches', 2)
            ->set("form.{$field}", -1)
            ->set('form.weight', 220)
            ->call('submitForm')
            ->assertHasErrors(["form.{$field}" => 'min']);
    })->with(['height_feet', 'height_inches']);

    it('saves a height of inches only', function () {
        livewire(FormModal::class)
            ->set('form.name', 'Short Wrestler')
            ->set('form.hometown', 'Test City, TX')
            ->set('form.height_feet', 0)
            ->set('form.height_inches', 5)
            ->set('form.weight', 220)
            ->call('submitForm')
            ->assertHasNoErrors();

        expect(Wrestler::where('name', 'Short Wrestler')->exists())->toBeTrue();
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
        expect($wrestler->name)->toBe('Updated Name')
            ->and($wrestler->hometown)->toBe('Updated City');
        $component->assertSuccessful();
    });

    it('rejects changing an active wrestler employment date', function () {
        $wrestler = Wrestler::factory()->create();
        $wrestler->employments()->create(['started_at' => '2024-01-15']);
        $component = livewire(FormModal::class);

        $component->call('openModal', $wrestler->id);
        $component->set('form.employment_date', '2024-01-01');
        $component->call('submitForm');

        $component->assertHasErrors(['form.employment_date']);
    });
});

describe('FormModal Dummy Data', function () {
    it('can fill dummy data', function () {
        $component = livewire(FormModal::class);
        $component->call('fillDummyFields');

        expect($component->get('form.name'))->not->toBeEmpty()
            ->and($component->get('form.hometown'))->not->toBeEmpty()
            ->and($component->get('form.height_feet'))->toBeBetween(5, 7)
            ->and($component->get('form.height_inches'))->toBeBetween(0, 11)
            ->and($component->get('form.weight'))->toBeBetween(180, 350);
    });

    it('generates realistic dummy data', function () {
        $component = livewire(FormModal::class);
        $component->call('fillDummyFields');

        // Check that height is realistic (5-7 feet)
        expect($component->get('form.height_feet'))->toBeBetween(5, 7);

        // Check that height inches is valid (0-11)
        expect($component->get('form.height_inches'))->toBeBetween(0, 11);

        // Check that weight is realistic (180-350)
        expect($component->get('form.weight'))->toBeBetween(180, 350);

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
            ->assertDispatched('refreshDatatable');
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

describe('FormModal employment history', function () {
    beforeEach(function () {
        travelTo(Date::parse('2024-06-01 12:00:00'));
    });

    it('keeps the employment history of a released, retired or future-employed wrestler when only the name changes', function (
        string $state,
        ?string $submittedDate,
    ) {
        // Arrange
        $wrestler = Wrestler::factory()->create(['name' => 'Original Name']);
        giveEmploymentHistory($wrestler, $state);
        $employmentsBefore = employmentSnapshot($wrestler);
        $statusBefore = $wrestler->fresh()?->status;
        $modal = livewire(FormModal::class);

        // Act
        $modal->call('openModal', $wrestler->id);
        $modal->set('form.name', 'Renamed Wrestler');
        $modal->set('form.employment_date', $submittedDate);
        $modal->call('save');

        // Assert
        $modal
            ->assertHasNoErrors()
            ->assertSet('isModalOpen', false);
        $wrestler->refresh();
        expect($wrestler->name)->toBe('Renamed Wrestler')
            ->and(employmentSnapshot($wrestler))->toBe($employmentsBefore)
            ->and($wrestler->status)->toBe($statusBefore);
    })->with([
        'released' => ['released', null],
        'retired' => ['retired', null],
        'future-employed' => ['future', null],
        'released with a submitted date' => ['released', '2024-04-01'],
        'retired with a submitted date' => ['retired', '2024-04-01'],
        'future-employed with a submitted date' => ['future', '2024-04-01'],
    ]);

    it('does not offer the employment date once the wrestler has an employment history', function (string $state) {
        // Arrange
        $wrestler = Wrestler::factory()->create();
        giveEmploymentHistory($wrestler, $state);
        $modal = livewire(FormModal::class);

        // Act
        $modal->call('openModal', $wrestler->id);

        // Assert
        $modal
            ->assertSet('form.hasEmploymentHistory', true)
            ->assertSet('form.employment_date', '')
            ->assertDontSeeHtml('wire:model="form.employment_date"');
    })->with(['released', 'retired', 'future']);

    it('still employs a never-employed wrestler from the submitted date', function () {
        // Arrange
        $wrestler = Wrestler::factory()->create(['name' => 'Original Name']);
        $modal = livewire(FormModal::class);

        // Act
        $modal->call('openModal', $wrestler->id);
        $modal->set('form.name', 'Renamed Wrestler');
        $modal->set('form.employment_date', '2024-02-01');
        $modal->call('save');

        // Assert
        $modal->assertHasNoErrors();
        $wrestler->refresh();
        expect($wrestler->name)->toBe('Renamed Wrestler')
            ->and($wrestler->status)->toBe(EmploymentStatus::Employed)
            ->and(employmentSnapshot($wrestler))->toBe([[
                'id' => $wrestler->employments()->firstOrFail()->id,
                'started_at' => '2024-02-01',
                'ended_at' => null,
            ]]);
    });

    it('shows a business rule failure on the name field instead of failing the request', function () {
        // Arrange
        $wrestler = Wrestler::factory()->create(['name' => 'Original Name']);
        $action = Double::for(UpdateAction::class);
        $action->expects('handle')->throws(CannotBeEmployedException::retired($wrestler));
        app()->instance(UpdateAction::class, $action);
        $modal = livewire(FormModal::class);

        // Act
        $modal->call('openModal', $wrestler->id);
        $modal->set('form.name', 'Renamed Wrestler');
        $modal->call('save');

        // Assert
        $modal
            ->assertHasErrors(['form.name'])
            ->assertSet('isModalOpen', true)
            ->assertNotDispatched('refreshDatatable');
        expect($wrestler->fresh()?->name)->toBe('Original Name');
        $action->verify();
    });
});
