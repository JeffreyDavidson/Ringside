<?php

declare(strict_types=1);

use App\Livewire\Venues\Modals\FormModal;
use App\Models\Events\Venue;
use App\Models\Geo\State;
use App\Models\Users\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->administrator()->create();
    $this->actingAs($this->admin);
    
    $this->state = State::factory()->create(['name' => 'California']);
});

describe('Form Modal Initialization', function () {
    it('can mount modal component', function () {
        $modal = Livewire::test(FormModal::class);
        
        $modal->assertOk();
        $modal->assertViewIs('livewire.venues.modals.form-modal');
    });

    it('initializes with empty form for creation', function () {
        $modal = Livewire::test(FormModal::class);
        
        $modal->assertSet('form.name', '');
        $modal->assertSet('form.street_address', '');
        $modal->assertSet('form.city', '');
        $modal->assertSet('form.state', '');
        $modal->assertSet('form.zipcode', '');
    });

    it('can open modal for creating new venue', function () {
        $modal = Livewire::test(FormModal::class)
            ->call('openModal');
        
        $modal->assertSet('isOpen', true);
        $modal->assertSet('form.name', '');
    });

    it('can close modal', function () {
        $modal = Livewire::test(FormModal::class)
            ->call('openModal')
            ->call('closeModal');
        
        $modal->assertSet('isOpen', false);
    });
});

describe('Form Modal Editing', function () {
    it('can load existing venue for editing', function () {
        $venue = Venue::factory()->create([
            'name' => 'Madison Square Garden',
            'street_address' => '4 Pennsylvania Plaza',
            'city' => 'New York',
            'state' => 'New York',
            'zipcode' => '10001',
        ]);

        $modal = Livewire::test(FormModal::class)
            ->call('openModal', $venue->id);

        $modal->assertSet('form.name', 'Madison Square Garden');
        $modal->assertSet('form.street_address', '4 Pennsylvania Plaza');
        $modal->assertSet('form.city', 'New York');
        $modal->assertSet('form.state', 'New York');
        $modal->assertSet('form.zipcode', '10001');
    });

    it('can update existing venue', function () {
        $venue = Venue::factory()->create([
            'name' => 'Original Arena',
            'street_address' => '123 Main St',
            'city' => 'Los Angeles',
            'state' => 'California',
            'zipcode' => '90210',
        ]);

        $modal = Livewire::test(FormModal::class)
            ->call('openModal', $venue->id)
            ->set('form.name', 'Updated Arena')
            ->set('form.street_address', '456 Oak Ave')
            ->set('form.city', 'San Francisco')
            ->set('form.zipcode', '94102')
            ->call('submit');

        $modal->assertHasNoErrors();
        $modal->assertDispatched('venueUpdated');
        $modal->assertSet('isOpen', false);

        $this->assertDatabaseHas('venues', [
            'id' => $venue->id,
            'name' => 'Updated Arena',
            'street_address' => '456 Oak Ave',
            'city' => 'San Francisco',
            'state' => 'California',
            'zipcode' => '94102',
        ]);
    });

    it('preserves venue data when validation fails', function () {
        $venue = Venue::factory()->create();

        $modal = Livewire::test(FormModal::class)
            ->call('openModal', $venue->id)
            ->set('form.name', 'Test Venue')
            ->set('form.street_address', '123 Test St')
            ->set('form.city', 'Test City')
            ->set('form.state', 'Invalid State')
            ->set('form.zipcode', '12345')
            ->call('submit');

        $modal->assertHasErrors(['form.state']);
        $modal->assertSet('form.name', 'Test Venue');
        $modal->assertSet('form.street_address', '123 Test St');
        $modal->assertSet('form.city', 'Test City');
        $modal->assertSet('form.zipcode', '12345');
    });
});

describe('Form Modal Creation', function () {
    it('can create new venue with valid data', function () {
        $modal = Livewire::test(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'New Wrestling Arena')
            ->set('form.street_address', '789 Wrestling Way')
            ->set('form.city', 'Sacramento')
            ->set('form.state', 'California')
            ->set('form.zipcode', '95814')
            ->call('submit');

        $modal->assertHasNoErrors();
        $modal->assertDispatched('venueCreated');
        $modal->assertSet('isOpen', false);

        $this->assertDatabaseHas('venues', [
            'name' => 'New Wrestling Arena',
            'street_address' => '789 Wrestling Way',
            'city' => 'Sacramento',
            'state' => 'California',
            'zipcode' => '95814',
        ]);
    });

    it('validates required fields', function () {
        $modal = Livewire::test(FormModal::class)
            ->call('openModal')
            ->set('form.name', '')
            ->set('form.street_address', '')
            ->set('form.city', '')
            ->set('form.state', '')
            ->set('form.zipcode', '')
            ->call('submit');

        $modal->assertHasErrors([
            'form.name' => 'required',
            'form.street_address' => 'required',
            'form.city' => 'required',
            'form.state' => 'required',
            'form.zipcode' => 'required',
        ]);
    });

    it('validates venue name uniqueness', function () {
        Venue::factory()->create(['name' => 'Existing Arena']);

        $modal = Livewire::test(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'Existing Arena')
            ->set('form.street_address', '123 New St')
            ->set('form.city', 'Los Angeles')
            ->set('form.state', 'California')
            ->set('form.zipcode', '90210')
            ->call('submit');

        $modal->assertHasErrors(['form.name' => 'unique']);
    });

    it('validates state exists in database', function () {
        $modal = Livewire::test(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'Test Arena')
            ->set('form.street_address', '123 Test St')
            ->set('form.city', 'Test City')
            ->set('form.state', 'Nonexistent State')
            ->set('form.zipcode', '12345')
            ->call('submit');

        $modal->assertHasErrors(['form.state' => 'exists']);
    });

    it('validates zipcode format', function () {
        $modal = Livewire::test(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'Test Arena')
            ->set('form.street_address', '123 Test St')
            ->set('form.city', 'Test City')
            ->set('form.state', 'California')
            ->set('form.zipcode', '123')
            ->call('submit');

        $modal->assertHasErrors(['form.zipcode' => 'digits']);
    });

    it('accepts valid zipcode format', function () {
        $modal = Livewire::test(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'Valid Arena')
            ->set('form.street_address', '123 Valid St')
            ->set('form.city', 'Valid City')
            ->set('form.state', 'California')
            ->set('form.zipcode', '12345')
            ->call('submit');

        $modal->assertHasNoErrors();
    });
});

describe('Form Modal Validation', function () {
    it('validates field lengths', function () {
        $longName = str_repeat('a', 256);
        $longAddress = str_repeat('a', 256);
        $longCity = str_repeat('a', 256);

        $modal = Livewire::test(FormModal::class)
            ->call('openModal')
            ->set('form.name', $longName)
            ->set('form.street_address', $longAddress)
            ->set('form.city', $longCity)
            ->set('form.state', 'California')
            ->set('form.zipcode', '12345')
            ->call('submit');

        $modal->assertHasErrors([
            'form.name' => 'max',
            'form.street_address' => 'max',
            'form.city' => 'max',
        ]);
    });

    it('accepts valid field lengths', function () {
        $validName = str_repeat('a', 255);
        $validAddress = str_repeat('a', 255);
        $validCity = str_repeat('a', 255);

        $modal = Livewire::test(FormModal::class)
            ->call('openModal')
            ->set('form.name', $validName)
            ->set('form.street_address', $validAddress)
            ->set('form.city', $validCity)
            ->set('form.state', 'California')
            ->set('form.zipcode', '12345')
            ->call('submit');

        $modal->assertHasNoErrors();
    });

    it('validates zipcode as numeric', function () {
        $modal = Livewire::test(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'Test Arena')
            ->set('form.street_address', '123 Test St')
            ->set('form.city', 'Test City')
            ->set('form.state', 'California')
            ->set('form.zipcode', 'abcde')
            ->call('submit');

        $modal->assertHasErrors(['form.zipcode' => 'digits']);
    });
});

describe('Form Modal State Management', function () {
    it('resets form after successful creation', function () {
        $modal = Livewire::test(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'Test Arena')
            ->set('form.street_address', '123 Test St')
            ->set('form.city', 'Test City')
            ->set('form.state', 'California')
            ->set('form.zipcode', '12345')
            ->call('submit');

        $modal->assertHasNoErrors();
        $modal->assertSet('form.name', '');
        $modal->assertSet('form.street_address', '');
        $modal->assertSet('form.city', '');
        $modal->assertSet('form.state', '');
        $modal->assertSet('form.zipcode', '');
    });

    it('preserves form state when validation fails', function () {
        $modal = Livewire::test(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'Test Arena')
            ->set('form.street_address', '123 Test St')
            ->set('form.city', 'Test City')
            ->set('form.state', 'Invalid State')
            ->set('form.zipcode', '12345')
            ->call('submit');

        $modal->assertHasErrors();
        $modal->assertSet('form.name', 'Test Arena');
        $modal->assertSet('form.street_address', '123 Test St');
        $modal->assertSet('form.city', 'Test City');
        $modal->assertSet('form.state', 'Invalid State');
        $modal->assertSet('form.zipcode', '12345');
    });

    it('handles modal close during form submission', function () {
        $modal = Livewire::test(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'Test Arena')
            ->call('closeModal');

        $modal->assertSet('isOpen', false);
        $modal->assertSet('form.name', '');
    });
});

describe('Form Modal Dummy Data', function () {
    it('can fill form with dummy data', function () {
        $modal = Livewire::test(FormModal::class)
            ->call('openModal')
            ->call('fillWithDummyData');

        $modal->assertSet('form.name', fn($value) => str_contains($value, 'Arena'));
        $modal->assertSet('form.street_address', fn($value) => !empty($value));
        $modal->assertSet('form.city', fn($value) => !empty($value));
        $modal->assertSet('form.state', fn($value) => !empty($value));
        $modal->assertSet('form.zipcode', fn($value) => is_numeric($value) && strlen((string)$value) === 5);
    });

    it('can submit form with dummy data', function () {
        $modal = Livewire::test(FormModal::class)
            ->call('openModal')
            ->call('fillWithDummyData')
            ->call('submit');

        $modal->assertHasNoErrors();
        $modal->assertDispatched('venueCreated');
    });
});
