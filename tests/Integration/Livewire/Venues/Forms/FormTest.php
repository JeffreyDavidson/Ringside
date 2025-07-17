<?php

declare(strict_types=1);

use App\Livewire\Venues\Forms\Form;
use App\Models\Events\Venue;
use App\Models\Geo\State;
use App\Models\Users\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->administrator()->create();
    $this->actingAs($this->admin);
    
    $this->state = State::factory()->create(['name' => 'California']);
});

describe('Form Validation Rules', function () {
    it('validates required fields', function () {
        $form = Livewire::test(Form::class)
            ->set('name', '')
            ->set('street_address', '')
            ->set('city', '')
            ->set('state', '')
            ->set('zipcode', '')
            ->call('store');

        $form->assertHasErrors([
            'name' => 'required',
            'street_address' => 'required',
            'city' => 'required',
            'state' => 'required',
            'zipcode' => 'required',
        ]);
    });

    it('validates venue name uniqueness', function () {
        Venue::factory()->create(['name' => 'Existing Arena']);

        $form = Livewire::test(Form::class)
            ->set('name', 'Existing Arena')
            ->set('street_address', '123 Main St')
            ->set('city', 'Los Angeles')
            ->set('state', 'California')
            ->set('zipcode', '90210')
            ->call('store');

        $form->assertHasErrors(['name' => 'unique']);
    });

    it('validates state exists in database', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'Test Arena')
            ->set('street_address', '123 Test St')
            ->set('city', 'Test City')
            ->set('state', 'Nonexistent State')
            ->set('zipcode', '12345')
            ->call('store');

        $form->assertHasErrors(['state' => 'exists']);
    });

    it('validates zipcode format', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'Test Arena')
            ->set('street_address', '123 Test St')
            ->set('city', 'Test City')
            ->set('state', 'California')
            ->set('zipcode', '123')
            ->call('store');

        $form->assertHasErrors(['zipcode' => 'digits']);
    });

    it('accepts valid zipcode format', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'Valid Arena')
            ->set('street_address', '123 Valid St')
            ->set('city', 'Valid City')
            ->set('state', 'California')
            ->set('zipcode', '12345')
            ->call('store');

        $form->assertHasNoErrors();
    });
});

describe('Form Field Validation', function () {
    it('validates field lengths', function () {
        $longName = str_repeat('a', 256);
        $longAddress = str_repeat('a', 256);
        $longCity = str_repeat('a', 256);

        $form = Livewire::test(Form::class)
            ->set('name', $longName)
            ->set('street_address', $longAddress)
            ->set('city', $longCity)
            ->set('state', 'California')
            ->set('zipcode', '12345')
            ->call('store');

        $form->assertHasErrors([
            'name' => 'max',
            'street_address' => 'max',
            'city' => 'max',
        ]);
    });

    it('accepts valid field lengths', function () {
        $validName = str_repeat('a', 255);
        $validAddress = str_repeat('a', 255);
        $validCity = str_repeat('a', 255);

        $form = Livewire::test(Form::class)
            ->set('name', $validName)
            ->set('street_address', $validAddress)
            ->set('city', $validCity)
            ->set('state', 'California')
            ->set('zipcode', '12345')
            ->call('store');

        $form->assertHasNoErrors();
    });

    it('validates zipcode as numeric', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'Test Arena')
            ->set('street_address', '123 Test St')
            ->set('city', 'Test City')
            ->set('state', 'California')
            ->set('zipcode', 'abcde')
            ->call('store');

        $form->assertHasErrors(['zipcode' => 'digits']);
    });

    it('validates zipcode exact length', function () {
        // Test too short
        $form = Livewire::test(Form::class)
            ->set('name', 'Test Arena')
            ->set('street_address', '123 Test St')
            ->set('city', 'Test City')
            ->set('state', 'California')
            ->set('zipcode', '1234')
            ->call('store');

        $form->assertHasErrors(['zipcode' => 'digits']);

        // Test too long
        $form = Livewire::test(Form::class)
            ->set('name', 'Test Arena')
            ->set('street_address', '123 Test St')
            ->set('city', 'Test City')
            ->set('state', 'California')
            ->set('zipcode', '123456')
            ->call('store');

        $form->assertHasErrors(['zipcode' => 'digits']);
    });
});

describe('Form Store Operations', function () {
    it('can store valid venue data', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'Wrestling Arena')
            ->set('street_address', '123 Wrestling Way')
            ->set('city', 'Los Angeles')
            ->set('state', 'California')
            ->set('zipcode', '90210')
            ->call('store');

        $form->assertHasNoErrors();
        $form->assertDispatched('venueCreated');

        $this->assertDatabaseHas('venues', [
            'name' => 'Wrestling Arena',
            'street_address' => '123 Wrestling Way',
            'city' => 'Los Angeles',
            'state' => 'California',
            'zipcode' => '90210',
        ]);
    });

    it('stores venue with all required fields', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'Complete Arena')
            ->set('street_address', '456 Complete St')
            ->set('city', 'Sacramento')
            ->set('state', 'California')
            ->set('zipcode', '95814')
            ->call('store');

        $form->assertHasNoErrors();

        $venue = Venue::where('name', 'Complete Arena')->first();
        expect($venue)->not->toBeNull();
        expect($venue->name)->toBe('Complete Arena');
        expect($venue->street_address)->toBe('456 Complete St');
        expect($venue->city)->toBe('Sacramento');
        expect($venue->state)->toBe('California');
        expect($venue->zipcode)->toBe('95814');
    });

    it('stores venue with proper data types', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'Type Test Arena')
            ->set('street_address', '789 Type Test St')
            ->set('city', 'San Francisco')
            ->set('state', 'California')
            ->set('zipcode', '94102')
            ->call('store');

        $form->assertHasNoErrors();

        $venue = Venue::where('name', 'Type Test Arena')->first();
        expect($venue->zipcode)->toBeString();
        expect($venue->zipcode)->toBe('94102');
    });
});

describe('Form Update Operations', function () {
    it('can update existing venue', function () {
        $venue = Venue::factory()->create([
            'name' => 'Original Arena',
            'street_address' => '123 Original St',
            'city' => 'Original City',
            'state' => 'California',
            'zipcode' => '90210',
        ]);

        $form = Livewire::test(Form::class)
            ->call('setModel', $venue)
            ->set('name', 'Updated Arena')
            ->set('street_address', '456 Updated Ave')
            ->set('city', 'Updated City')
            ->set('state', 'California')
            ->set('zipcode', '94102')
            ->call('update');

        $form->assertHasNoErrors();
        $form->assertDispatched('venueUpdated');

        $this->assertDatabaseHas('venues', [
            'id' => $venue->id,
            'name' => 'Updated Arena',
            'street_address' => '456 Updated Ave',
            'city' => 'Updated City',
            'state' => 'California',
            'zipcode' => '94102',
        ]);
    });

    it('validates name uniqueness excluding current venue when updating', function () {
        $venue1 = Venue::factory()->create(['name' => 'Venue One']);
        $venue2 = Venue::factory()->create(['name' => 'Venue Two']);

        $form = Livewire::test(Form::class)
            ->call('setModel', $venue2)
            ->set('name', 'Venue One')
            ->set('street_address', $venue2->street_address)
            ->set('city', $venue2->city)
            ->set('state', $venue2->state)
            ->set('zipcode', $venue2->zipcode)
            ->call('update');

        $form->assertHasErrors(['name' => 'unique']);
    });

    it('allows keeping same name when updating', function () {
        $venue = Venue::factory()->create([
            'name' => 'Same Name Arena',
            'street_address' => '123 Same St',
            'city' => 'Same City',
            'state' => 'California',
            'zipcode' => '90210',
        ]);

        $form = Livewire::test(Form::class)
            ->call('setModel', $venue)
            ->set('name', 'Same Name Arena')
            ->set('street_address', '456 Different St')
            ->set('city', 'Different City')
            ->set('state', 'California')
            ->set('zipcode', '94102')
            ->call('update');

        $form->assertHasNoErrors();
        $form->assertDispatched('venueUpdated');
    });

    it('can update partial venue data', function () {
        $venue = Venue::factory()->create([
            'name' => 'Partial Arena',
            'street_address' => '123 Partial St',
            'city' => 'Partial City',
            'state' => 'California',
            'zipcode' => '90210',
        ]);

        $form = Livewire::test(Form::class)
            ->call('setModel', $venue)
            ->set('name', 'Partial Arena')
            ->set('street_address', '456 New Address')
            ->set('city', 'Partial City')
            ->set('state', 'California')
            ->set('zipcode', '90210')
            ->call('update');

        $form->assertHasNoErrors();

        $venue->refresh();
        expect($venue->street_address)->toBe('456 New Address');
        expect($venue->city)->toBe('Partial City');
    });
});

describe('Form State Management', function () {
    it('resets form after successful store', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'Test Arena')
            ->set('street_address', '123 Test St')
            ->set('city', 'Test City')
            ->set('state', 'California')
            ->set('zipcode', '12345')
            ->call('store');

        $form->assertHasNoErrors();
        $form->assertSet('name', '');
        $form->assertSet('street_address', '');
        $form->assertSet('city', '');
        $form->assertSet('state', '');
        $form->assertSet('zipcode', '');
    });

    it('preserves form state when validation fails', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'Test Arena')
            ->set('street_address', '123 Test St')
            ->set('city', 'Test City')
            ->set('state', 'Invalid State')
            ->set('zipcode', '12345')
            ->call('store');

        $form->assertHasErrors();
        $form->assertSet('name', 'Test Arena');
        $form->assertSet('street_address', '123 Test St');
        $form->assertSet('city', 'Test City');
        $form->assertSet('state', 'Invalid State');
        $form->assertSet('zipcode', '12345');
    });

    it('loads existing model data correctly', function () {
        $venue = Venue::factory()->create([
            'name' => 'Load Test Arena',
            'street_address' => '789 Load St',
            'city' => 'Load City',
            'state' => 'California',
            'zipcode' => '95814',
        ]);

        $form = Livewire::test(Form::class)
            ->call('setModel', $venue);

        $form->assertSet('name', 'Load Test Arena');
        $form->assertSet('street_address', '789 Load St');
        $form->assertSet('city', 'Load City');
        $form->assertSet('state', 'California');
        $form->assertSet('zipcode', '95814');
    });
});

describe('Form Address Validation', function () {
    it('validates complete address information', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'Address Test Arena')
            ->set('street_address', '1234 Main Street')
            ->set('city', 'Los Angeles')
            ->set('state', 'California')
            ->set('zipcode', '90210')
            ->call('store');

        $form->assertHasNoErrors();

        $venue = Venue::where('name', 'Address Test Arena')->first();
        expect($venue->street_address)->toBe('1234 Main Street');
        expect($venue->city)->toBe('Los Angeles');
        expect($venue->state)->toBe('California');
        expect($venue->zipcode)->toBe('90210');
    });

    it('handles various address formats', function () {
        $addresses = [
            '123 Main St',
            '456 Oak Avenue',
            '789 Pine Boulevard',
            '1010 Wrestling Way',
        ];

        foreach ($addresses as $index => $address) {
            $form = Livewire::test(Form::class)
                ->set('name', "Arena {$index}")
                ->set('street_address', $address)
                ->set('city', 'Test City')
                ->set('state', 'California')
                ->set('zipcode', '12345')
                ->call('store');

            $form->assertHasNoErrors();
            
            $this->assertDatabaseHas('venues', [
                'name' => "Arena {$index}",
                'street_address' => $address,
            ]);
        }
    });

    it('validates state must exist in states table', function () {
        // Test with known invalid state
        $form = Livewire::test(Form::class)
            ->set('name', 'State Test Arena')
            ->set('street_address', '123 State St')
            ->set('city', 'State City')
            ->set('state', 'Invalid State Name')
            ->set('zipcode', '12345')
            ->call('store');

        $form->assertHasErrors(['state' => 'exists']);
    });

    it('accepts valid state from states table', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'Valid State Arena')
            ->set('street_address', '123 Valid St')
            ->set('city', 'Valid City')
            ->set('state', 'California')
            ->set('zipcode', '12345')
            ->call('store');

        $form->assertHasNoErrors();
    });
});