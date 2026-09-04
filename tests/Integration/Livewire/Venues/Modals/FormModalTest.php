<?php

declare(strict_types=1);

use App\Livewire\Venues\Modals\FormModal;
use App\Models\Events\Venue;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('authorized venue form interactions', function () {
    beforeEach(function () {
        actingAs(administrator());
    });

    it('renders the venue form fields', function () {
        $modal = livewire(FormModal::class);

        $modal->assertSuccessful();
        $modal->assertViewIs('livewire.venues.modals.form-modal');
        $modal
            ->assertPropertyWired('form.name')
            ->assertPropertyWired('form.street_address')
            ->assertPropertyWired('form.city')
            ->assertPropertyWired('form.state')
            ->assertPropertyWired('form.zipcode');
    });

    it('opens an empty form for creating a venue', function () {
        $modal = livewire(FormModal::class);
        $modal->call('openModal');

        $modal
            ->assertSet('isModalOpen', true)
            ->assertSet('form.name', '')
            ->assertSet('form.street_address', '')
            ->assertSet('form.city', '')
            ->assertSet('form.state', '')
            ->assertSet('form.zipcode', '');
    });

    it('closes the modal and clears unsaved venue data', function () {
        $modal = livewire(FormModal::class);
        $modal->call('openModal');
        $modal->set('form.name', 'Unsaved Arena');
        $modal->call('closeModal');

        $modal
            ->assertSet('isModalOpen', false)
            ->assertSet('form.name', '');
    });

    it('loads an existing venue for editing', function () {
        $venue = Venue::factory()->create([
            'name' => 'Madison Square Garden',
            'street_address' => '4 Pennsylvania Plaza',
            'city' => 'New York',
            'state' => 'New York',
            'zipcode' => '10001',
        ]);

        $modal = livewire(FormModal::class);
        $modal->call('openModal', $venue->id);
        $modal->set('form.name', 'Madison Square Garden');

        $modal
            ->assertSet('isModalOpen', true)
            ->assertSet('form.name', 'Madison Square Garden')
            ->assertSet('form.street_address', '4 Pennsylvania Plaza')
            ->assertSet('form.city', 'New York')
            ->assertSet('form.state', 'New York')
            ->assertSet('form.zipcode', '10001')
            ->assertSee('Edit Venue');
    });

    it('propagates a missing venue failure', function () {
        expect(fn () => livewire(FormModal::class)->call('openModal', PHP_INT_MAX))
            ->toThrow(ModelNotFoundException::class);
    });

    it('creates a venue and resets the modal', function () {
        $modal = livewire(FormModal::class);
        $modal->call('openModal');
        $modal->set([
            'form.name' => 'New Wrestling Arena',
            'form.street_address' => '789 Wrestling Way',
            'form.city' => 'Sacramento',
            'form.state' => 'California',
            'form.zipcode' => '95814',
        ]);
        $modal->call('save');

        $venue = Venue::query()->whereName('New Wrestling Arena')->firstOrFail();
        expect($venue->street_address)->toBe('789 Wrestling Way')
            ->and($venue->city)->toBe('Sacramento')
            ->and($venue->state)->toBe('California')
            ->and($venue->zipcode)->toBe('95814');
        $modal
            ->assertHasNoErrors()
            ->assertDispatched('venueCreated')
            ->assertDispatched('refreshDatatable')
            ->assertSet('isModalOpen', false)
            ->assertSet('form.name', '');
    });

    it('updates an existing venue and resets the modal', function () {
        $venue = Venue::factory()->create([
            'name' => 'Original Arena',
            'state' => 'California',
        ]);

        $modal = livewire(FormModal::class);
        $modal->call('openModal', $venue->id);
        $modal->set([
            'form.name' => 'Updated Arena',
            'form.street_address' => '456 Oak Avenue',
            'form.city' => 'San Francisco',
            'form.state' => 'California',
            'form.zipcode' => '94102',
        ]);
        $modal->call('save');

        $venue->refresh();
        expect($venue->name)->toBe('Updated Arena')
            ->and($venue->street_address)->toBe('456 Oak Avenue')
            ->and($venue->city)->toBe('San Francisco')
            ->and($venue->zipcode)->toBe('94102');
        $modal
            ->assertHasNoErrors()
            ->assertDispatched('venueUpdated')
            ->assertSet('isModalOpen', false)
            ->assertSet('form.name', '');
    });

    it('requires complete venue data', function () {
        $modal = livewire(FormModal::class);
        $modal->call('openModal');
        $modal->call('save');

        $modal->assertHasErrors([
            'form.name' => 'required',
            'form.street_address' => 'required',
            'form.city' => 'required',
            'form.state' => 'required',
            'form.zipcode' => 'required',
        ]);
    });

    it('rejects invalid venue field values', function (string $field, mixed $value, string $rule) {
        $modal = livewire(FormModal::class);
        $modal->call('openModal');
        $modal->set([
            'form.name' => 'Valid Arena',
            'form.street_address' => '123 Valid Street',
            'form.city' => 'Valid City',
            'form.state' => 'California',
            'form.zipcode' => '12345',
        ]);
        $modal->set($field, $value);
        $modal->call('save');

        $modal->assertHasErrors([$field => $rule]);
    })->with([
        'long name' => ['form.name', str_repeat('a', 256), 'max'],
        'long street address' => ['form.street_address', str_repeat('a', 256), 'max'],
        'long city' => ['form.city', str_repeat('a', 256), 'max'],
        'unsupported state' => ['form.state', 'Invalid State', 'Illuminate\\Validation\\Rules\\Enum'],
        'short zipcode' => ['form.zipcode', '123', 'digits'],
        'non-numeric zipcode' => ['form.zipcode', 'abcde', 'digits'],
    ]);

    it('rejects a duplicate venue name while editing the original name', function () {
        Venue::factory()->create(['name' => 'Existing Arena']);
        $venue = Venue::factory()->create(['name' => 'Editable Arena']);

        $modal = livewire(FormModal::class);
        $modal->call('openModal', $venue->id);
        $modal->set('form.name', 'Existing Arena');
        $modal->call('save');

        $modal
            ->assertHasErrors(['form.name' => 'unique'])
            ->assertSet('form.name', 'Existing Arena')
            ->assertSet('isModalOpen', true);
    });

    it('generates valid dummy data that can create a venue', function () {
        $modal = livewire(FormModal::class);
        $modal->call('openModal');
        $modal->call('fillDummyFields');
        $modal->call('save');

        $modal
            ->assertHasNoErrors()
            ->assertDispatched('venueCreated')
            ->assertSet('isModalOpen', false);
        expect(Venue::query()->count())->toBe(1);
    });
});

it('forbids users without administrative access from opening the venue form', function (string $actor) {
    if ($actor === 'basic user') {
        actingAs(basicUser());
    }

    $modal = livewire(FormModal::class);
    $modal->call('openModal');

    $modal->assertForbidden();
})->with([
    'guest' => ['guest'],
    'basic user' => ['basic user'],
]);
