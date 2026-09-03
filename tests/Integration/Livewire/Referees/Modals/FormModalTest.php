<?php

declare(strict_types=1);

use App\Livewire\Referees\Modals\FormModal;
use App\Models\Roster\Referees\Referee;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('authorized referee form interactions', function () {
    beforeEach(function () {
        actingAs(administrator());
    });

    it('renders the referee form fields', function () {
        $modal = livewire(FormModal::class);

        $modal->assertSuccessful();
        $modal->assertViewIs('livewire.referees.modals.form-modal');
        $modal
            ->assertPropertyWired('form.first_name')
            ->assertPropertyWired('form.last_name')
            ->assertPropertyWired('form.employment_date');
    });

    it('opens an empty form for creating a referee', function () {
        $modal = livewire(FormModal::class);

        $modal->call('openModal');

        $modal
            ->assertSet('isModalOpen', true)
            ->assertSet('form.first_name', '')
            ->assertSet('form.last_name', '')
            ->assertSet('form.employment_date', null)
            ->assertSee('Add Referee');
    });

    it('loads an existing referee for editing', function () {
        $referee = Referee::factory()->create([
            'first_name' => 'Earl',
            'last_name' => 'Hebner',
        ]);
        $referee->employments()->create(['started_at' => '2024-01-15']);
        $modal = livewire(FormModal::class);

        $modal->call('openModal', $referee->id);

        $modal
            ->assertSet('isModalOpen', true)
            ->assertSet('form.first_name', 'Earl')
            ->assertSet('form.last_name', 'Hebner')
            ->assertSet('form.employment_date', '2024-01-15')
            ->assertSee('Edit Earl Hebner');
    });

    it('propagates a missing referee failure', function () {
        expect(fn () => livewire(FormModal::class)->call('openModal', PHP_INT_MAX))
            ->toThrow(ModelNotFoundException::class);
    });

    it('creates an employed referee', function () {
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
        $modal->set([
            'form.first_name' => 'Mike',
            'form.last_name' => 'Chioda',
            'form.employment_date' => '2024-02-01',
        ]);
        $modal->call('save');

        $referee = Referee::query()
            ->where('first_name', 'Mike')
            ->where('last_name', 'Chioda')
            ->firstOrFail();
        expect($referee->firstEmployment?->started_at?->toDateString())->toBe('2024-02-01');
        $modal
            ->assertHasNoErrors()
            ->assertDispatched('refreshDatatable')
            ->assertDispatched('form-submitted')
            ->assertDispatched('closeModal')
            ->assertSet('isModalOpen', false);
    });

    it('creates a referee without optional employment data', function () {
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
        $modal->set([
            'form.first_name' => 'Charles',
            'form.last_name' => 'Robinson',
        ]);
        $modal->call('save');

        $referee = Referee::query()
            ->where('first_name', 'Charles')
            ->where('last_name', 'Robinson')
            ->firstOrFail();
        expect($referee->firstEmployment)->toBeNull();
        $modal->assertHasNoErrors();
    });

    it('updates a referee without replacing current employment', function () {
        $referee = Referee::factory()->create([
            'first_name' => 'Nick',
            'last_name' => 'Patrick',
        ]);
        $employment = $referee->employments()->create(['started_at' => '2024-01-15']);
        $modal = livewire(FormModal::class);

        $modal->call('openModal', $referee->id);
        $modal->set([
            'form.first_name' => 'Nicholas',
            'form.last_name' => 'Patrick',
        ]);
        $modal->call('save');

        $referee->refresh();
        expect($referee->first_name)->toBe('Nicholas')
            ->and($referee->last_name)->toBe('Patrick')
            ->and($referee->employments()->count())->toBe(1)
            ->and($referee->currentEmployment()->firstOrFail()->is($employment))->toBeTrue();
        $modal
            ->assertHasNoErrors()
            ->assertDispatched('refreshDatatable')
            ->assertSet('isModalOpen', false);
    });

    it('requires both referee names', function () {
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
        expect(Referee::query()->doesntExist())->toBeTrue();
    });

    it('rejects invalid referee field values', function (string $case) {
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
            'form.last_name' => 'Referee',
        ]);
        $modal->set($field, $value);
        $modal->call('save');

        $modal->assertHasErrors([$field => $rule]);
        expect(Referee::query()->doesntExist())->toBeTrue();
    })->with([
        'long first name',
        'long last name',
        'invalid employment date',
    ]);

    it('clears entered values when creating a referee', function () {
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
        $modal->set([
            'form.first_name' => 'Entered',
            'form.last_name' => 'Name',
            'form.employment_date' => '2024-01-15',
        ]);
        $modal->call('clear');

        $modal
            ->assertSet('form.first_name', '')
            ->assertSet('form.last_name', '')
            ->assertSet('form.employment_date', '');
    });

    it('restores original values when clearing an edited referee', function () {
        $referee = Referee::factory()->create([
            'first_name' => 'Original',
            'last_name' => 'Referee',
        ]);
        $referee->employments()->create(['started_at' => '2024-01-15']);
        $modal = livewire(FormModal::class);

        $modal->call('openModal', $referee->id);
        $modal->set([
            'form.first_name' => 'Changed',
            'form.last_name' => 'Name',
            'form.employment_date' => '2024-02-01',
        ]);
        $modal->call('clear');

        $modal
            ->assertSet('form.first_name', 'Original')
            ->assertSet('form.last_name', 'Referee')
            ->assertSet('form.employment_date', '2024-01-15');
    });

    it('resets edited referee data when reopening in create mode', function () {
        $referee = Referee::factory()->employed()->create([
            'first_name' => 'Existing',
            'last_name' => 'Referee',
        ]);
        $modal = livewire(FormModal::class);

        $modal->call('openModal', $referee->id);
        $modal->call('openModal');

        $modal
            ->assertSet('form.first_name', '')
            ->assertSet('form.last_name', '')
            ->assertSet('form.employment_date', null);
    });

    it('generates valid dummy data that can create a referee', function () {
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
        $modal->call('fillDummyFields');
        $modal->call('save');

        $modal
            ->assertHasNoErrors()
            ->assertDispatched('form-submitted')
            ->assertSet('isModalOpen', false);
        expect(Referee::query()->count())->toBe(1);
    });
});

it('forbids users without administrative access from opening the referee form', function (string $actor, string $operation) {
    $referee = $operation === 'update' ? Referee::factory()->create() : null;

    if ($actor === 'basic user') {
        actingAs(basicUser());
    }

    $modal = livewire(FormModal::class);
    $modal->call('openModal', $referee?->id);

    $modal->assertForbidden();
})->with([
    'guest creating' => ['guest', 'create'],
    'basic user creating' => ['basic user', 'create'],
    'guest updating' => ['guest', 'update'],
    'basic user updating' => ['basic user', 'update'],
]);
