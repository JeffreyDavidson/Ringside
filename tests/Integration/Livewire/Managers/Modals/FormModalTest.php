<?php

declare(strict_types=1);

use App\Livewire\Managers\Modals\FormModal;
use App\Models\Roster\Managers\Manager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('authorized manager form interactions', function () {
    beforeEach(function () {
        actingAs(administrator());
    });

    it('renders the manager form fields', function () {
        $modal = livewire(FormModal::class);

        $modal->assertSuccessful();
        $modal->assertViewIs('livewire.managers.modals.form-modal');
        $modal
            ->assertPropertyWired('form.first_name')
            ->assertPropertyWired('form.last_name')
            ->assertPropertyWired('form.employment_date');
    });

    it('opens an empty form for creating a manager', function () {
        $modal = livewire(FormModal::class);

        $modal->call('openModal');

        $modal
            ->assertSet('isModalOpen', true)
            ->assertSet('form.first_name', '')
            ->assertSet('form.last_name', '')
            ->assertSet('form.employment_date', null)
            ->assertSee('Add Manager');
    });

    it('loads an existing manager for editing', function () {
        $manager = Manager::factory()->create([
            'first_name' => 'Bobby',
            'last_name' => 'Heenan',
        ]);
        $manager->employments()->create(['started_at' => '2024-01-15']);
        $modal = livewire(FormModal::class);

        $modal->call('openModal', $manager->id);

        $modal
            ->assertSet('isModalOpen', true)
            ->assertSet('form.first_name', 'Bobby')
            ->assertSet('form.last_name', 'Heenan')
            ->assertSet('form.employment_date', '2024-01-15')
            ->assertSee('Edit Bobby Heenan');
    });

    it('propagates a missing manager failure', function () {
        expect(fn () => livewire(FormModal::class)->call('openModal', PHP_INT_MAX))
            ->toThrow(ModelNotFoundException::class);
    });

    it('creates an employed manager', function () {
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
        $modal->set([
            'form.first_name' => 'Paul',
            'form.last_name' => 'Dangerously',
            'form.employment_date' => '2024-02-01',
        ]);
        $modal->call('save');

        $manager = Manager::query()
            ->where('first_name', 'Paul')
            ->where('last_name', 'Dangerously')
            ->firstOrFail();
        expect($manager->firstEmployment?->started_at?->toDateString())->toBe('2024-02-01');
        $modal
            ->assertHasNoErrors()
            ->assertDispatched('refreshDatatable')
            ->assertDispatched('form-submitted')
            ->assertDispatched('closeModal')
            ->assertSet('isModalOpen', false);
    });

    it('creates a manager without optional employment data', function () {
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
        $modal->set([
            'form.first_name' => 'Jimmy',
            'form.last_name' => 'Hart',
        ]);
        $modal->call('save');

        $manager = Manager::query()
            ->where('first_name', 'Jimmy')
            ->where('last_name', 'Hart')
            ->firstOrFail();
        expect($manager->firstEmployment)->toBeNull();
        $modal->assertHasNoErrors();
    });

    it('updates a manager without replacing current employment', function () {
        $manager = Manager::factory()->create([
            'first_name' => 'James',
            'last_name' => 'Dillon',
        ]);
        $employment = $manager->employments()->create(['started_at' => '2024-01-15']);
        $modal = livewire(FormModal::class);

        $modal->call('openModal', $manager->id);
        $modal->set([
            'form.first_name' => 'J. J.',
            'form.last_name' => 'Dillon',
        ]);
        $modal->call('save');

        $manager->refresh();
        expect($manager->first_name)->toBe('J. J.')
            ->and($manager->last_name)->toBe('Dillon')
            ->and($manager->employments()->count())->toBe(1)
            ->and($manager->currentEmployment()->firstOrFail()->is($employment))->toBeTrue();
        $modal
            ->assertHasNoErrors()
            ->assertDispatched('refreshDatatable')
            ->assertSet('isModalOpen', false);
    });

    it('requires both manager names', function () {
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
        $modal->call('save');

        $modal
            ->assertHasErrors([
                'form.first_name' => 'required',
                'form.last_name' => 'required',
            ])
            ->assertNotDispatched('closeModal')
            ->assertSet('isModalOpen', true);
        expect(Manager::query()->doesntExist())->toBeTrue();
    });

    it('rejects invalid manager field values', function (string $case) {
        [$field, $value, $rule] = match ($case) {
            'long first name' => ['form.first_name', str_repeat('a', 256), 'max'],
            'long last name' => ['form.last_name', str_repeat('a', 256), 'max'],
            'invalid employment date' => ['form.employment_date', 'not-a-date', 'date'],
            default => throw new InvalidArgumentException("Unknown validation case: {$case}"),
        };
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
        $modal->set([
            'form.first_name' => 'Valid',
            'form.last_name' => 'Manager',
        ]);
        $modal->set($field, $value);
        $modal->call('save');

        $modal->assertHasErrors([$field => $rule]);
        expect(Manager::query()->doesntExist())->toBeTrue();
    })->with([
        'long first name',
        'long last name',
        'invalid employment date',
    ]);

    it('resets edited manager data when reopening in create mode', function () {
        $manager = Manager::factory()->employed()->create([
            'first_name' => 'Existing',
            'last_name' => 'Manager',
        ]);
        $modal = livewire(FormModal::class);

        $modal->call('openModal', $manager->id);
        $modal->call('openModal');

        $modal
            ->assertSet('form.first_name', '')
            ->assertSet('form.last_name', '')
            ->assertSet('form.employment_date', null);
    });

    it('generates valid dummy data that can create a manager', function () {
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
        $modal->call('fillDummyFields');
        $modal->call('save');

        $modal
            ->assertHasNoErrors()
            ->assertDispatched('form-submitted')
            ->assertSet('isModalOpen', false);
        expect(Manager::query()->count())->toBe(1);
    });
});

it('forbids users without administrative access from opening the manager form', function (string $actor, string $operation) {
    $manager = $operation === 'update' ? Manager::factory()->create() : null;

    if ($actor === 'basic user') {
        actingAs(basicUser());
    }

    $modal = livewire(FormModal::class);
    $modal->call('openModal', $manager?->id);

    $modal->assertForbidden();
})->with([
    'guest creating' => ['guest', 'create'],
    'basic user creating' => ['basic user', 'create'],
    'guest updating' => ['guest', 'update'],
    'basic user updating' => ['basic user', 'update'],
]);
