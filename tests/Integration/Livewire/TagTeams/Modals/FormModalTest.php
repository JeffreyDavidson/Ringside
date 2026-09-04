<?php

declare(strict_types=1);

use App\Livewire\TagTeams\Modals\FormModal;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('authorized tag team form interactions', function () {
    beforeEach(function () {
        actingAs(administrator());
    });

    it('renders the tag team fields and available choices', function () {
        Wrestler::factory()->create(['name' => 'Ricky Morton']);
        Manager::factory()->create(['first_name' => 'Bobby', 'last_name' => 'Heenan']);

        $modal = livewire(FormModal::class);

        $modal->assertSuccessful();
        $modal->assertViewIs('livewire.tag-teams.modals.form-modal');
        $modal
            ->assertPropertyWired('form.name')
            ->assertPropertyWired('form.signature_move')
            ->assertPropertyWired('form.wrestlerA')
            ->assertPropertyWired('form.wrestlerB')
            ->assertPropertyWired('form.managers')
            ->assertPropertyWired('form.employment_date')
            ->assertSee('Ricky Morton')
            ->assertSee('Bobby Heenan');
    });

    it('opens an empty form for creating a tag team', function () {
        $modal = livewire(FormModal::class);

        $modal->call('openModal');

        $modal
            ->assertSet('isModalOpen', true)
            ->assertSet('form.name', '')
            ->assertSet('form.signature_move', '')
            ->assertSet('form.wrestlerA', null)
            ->assertSet('form.wrestlerB', null)
            ->assertSet('form.managers', [])
            ->assertSet('form.employment_date', '')
            ->assertSee('Add TagTeam');
    });

    it('loads an existing tag team for editing', function () {
        $wrestlers = Wrestler::factory()->count(2)->create();
        $wrestlerA = $wrestlers->firstOrFail();
        $wrestlerB = $wrestlers->skip(1)->firstOrFail();
        $manager = Manager::factory()->create();
        $tagTeam = TagTeam::factory()->create([
            'name' => 'The Midnight Express',
            'signature_move' => 'Veg-O-Matic',
        ]);
        $tagTeam->wrestlers()->attach($wrestlers->modelKeys(), ['joined_at' => now()->subYear()]);
        $tagTeam->managers()->attach($manager, ['hired_at' => now()->subYear()]);
        $tagTeam->employments()->create(['started_at' => '2024-01-15']);
        $modal = livewire(FormModal::class);

        $modal->call('openModal', $tagTeam->id);
        $modal->set('form.name', 'The Midnight Express');

        $modal
            ->assertSet('isModalOpen', true)
            ->assertSet('form.name', 'The Midnight Express')
            ->assertSet('form.signature_move', 'Veg-O-Matic')
            ->assertSet('form.wrestlerA', $wrestlerA->id)
            ->assertSet('form.wrestlerB', $wrestlerB->id)
            ->assertSet('form.managers', [$manager->id])
            ->assertSet('form.employment_date', '2024-01-15')
            ->assertSee('Edit The Midnight Express');
    });

    it('propagates a missing tag team failure', function () {
        expect(fn () => livewire(FormModal::class)->call('openModal', PHP_INT_MAX))
            ->toThrow(ModelNotFoundException::class);
    });

    it('creates a tag team with its complete roster configuration', function () {
        $wrestlers = Wrestler::factory()->count(2)->create();
        $wrestlerA = $wrestlers->firstOrFail();
        $wrestlerB = $wrestlers->skip(1)->firstOrFail();
        $manager = Manager::factory()->create();
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
        $modal->set([
            'form.name' => 'The Road Warriors',
            'form.signature_move' => 'Doomsday Device',
            'form.wrestlerA' => $wrestlerA->id,
            'form.wrestlerB' => $wrestlerB->id,
            'form.managers' => [$manager->id],
            'form.employment_date' => '2024-02-01',
        ]);
        $modal->call('save');

        $tagTeam = TagTeam::query()->whereName('The Road Warriors')->firstOrFail();
        expect($tagTeam->signature_move)->toBe('Doomsday Device')
            ->and($tagTeam->currentWrestlers()->pluck('wrestlers.id')->sort()->values()->all())
            ->toBe($wrestlers->modelKeys())
            ->and($tagTeam->currentManagers()->pluck('managers.id')->all())->toBe([$manager->id])
            ->and($tagTeam->firstEmployment?->started_at?->toDateString())->toBe('2024-02-01');
        $modal
            ->assertHasNoErrors()
            ->assertDispatched('refreshDatatable')
            ->assertDispatched('form-submitted')
            ->assertDispatched('closeModal')
            ->assertSet('isModalOpen', false);
    });

    it('creates a tag team without optional profile or employment data', function () {
        $wrestlers = Wrestler::factory()->count(2)->create();
        $wrestlerA = $wrestlers->firstOrFail();
        $wrestlerB = $wrestlers->skip(1)->firstOrFail();
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
        $modal->set([
            'form.name' => 'The Rockers',
            'form.wrestlerA' => $wrestlerA->id,
            'form.wrestlerB' => $wrestlerB->id,
        ]);
        $modal->call('save');

        $tagTeam = TagTeam::query()->whereName('The Rockers')->firstOrFail();
        expect($tagTeam->signature_move)->toBeNull()
            ->and($tagTeam->currentManagers()->exists())->toBeFalse()
            ->and($tagTeam->firstEmployment)->toBeNull();
        $modal->assertHasNoErrors();
    });

    it('updates a tag team while preserving former roster relationships', function () {
        $originalWrestlers = Wrestler::factory()->count(2)->create();
        $newWrestlers = Wrestler::factory()->count(2)->create();
        $newWrestlerA = $newWrestlers->firstOrFail();
        $newWrestlerB = $newWrestlers->skip(1)->firstOrFail();
        $originalManager = Manager::factory()->create();
        $newManager = Manager::factory()->create();
        $tagTeam = TagTeam::factory()->create(['name' => 'Original Team', 'signature_move' => 'Original Move']);
        $tagTeam->wrestlers()->attach($originalWrestlers->modelKeys(), ['joined_at' => now()->subYear()]);
        $tagTeam->managers()->attach($originalManager, ['hired_at' => now()->subYear()]);
        $modal = livewire(FormModal::class);

        $modal->call('openModal', $tagTeam->id);
        $modal->set([
            'form.name' => 'Updated Team',
            'form.signature_move' => 'Updated Move',
            'form.wrestlerA' => $newWrestlerA->id,
            'form.wrestlerB' => $newWrestlerB->id,
            'form.managers' => [$newManager->id],
        ]);
        $modal->call('save');

        $tagTeam->refresh();
        expect($tagTeam->name)->toBe('Updated Team')
            ->and($tagTeam->signature_move)->toBe('Updated Move')
            ->and($tagTeam->currentWrestlers()->pluck('wrestlers.id')->sort()->values()->all())
            ->toBe($newWrestlers->modelKeys())
            ->and($tagTeam->previousWrestlers()->pluck('wrestlers.id')->sort()->values()->all())
            ->toBe($originalWrestlers->modelKeys())
            ->and($tagTeam->currentManagers()->pluck('managers.id')->all())->toBe([$newManager->id])
            ->and($tagTeam->previousManagers()->pluck('managers.id')->all())->toBe([$originalManager->id]);
        $modal
            ->assertHasNoErrors()
            ->assertDispatched('refreshDatatable')
            ->assertSet('isModalOpen', false);
    });

    it('rejects changing an active tag team employment date', function () {
        $wrestlers = Wrestler::factory()->count(2)->create();
        $tagTeam = TagTeam::factory()->create();
        $tagTeam->wrestlers()->attach($wrestlers->modelKeys(), ['joined_at' => '2024-01-15']);
        $tagTeam->employments()->create(['started_at' => '2024-01-15']);
        $modal = livewire(FormModal::class);

        $modal->call('openModal', $tagTeam->id);
        $modal->set('form.employment_date', '2024-01-01');
        $modal->call('save');

        $modal
            ->assertHasErrors(['form.employment_date'])
            ->assertSet('isModalOpen', true);
    });

    it('requires a name and two wrestlers', function () {
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
        $modal->call('save');

        $modal
            ->assertHasErrors([
                'form.name' => 'required',
                'form.wrestlerA' => 'required',
                'form.wrestlerB' => 'required',
            ])
            ->assertNotDispatched('closeModal')
            ->assertSet('isModalOpen', true);
        expect(TagTeam::query()->doesntExist())->toBeTrue();
    });

    it('rejects invalid tag team field values', function (string $case) {
        $wrestlers = Wrestler::factory()->count(2)->create();
        $wrestlerA = $wrestlers->firstOrFail();
        $wrestlerB = $wrestlers->skip(1)->firstOrFail();
        [$field, $value, $errorField, $rule] = match ($case) {
            'long name' => ['form.name', str_repeat('a', 256), 'form.name', 'max'],
            'long signature move' => ['form.signature_move', str_repeat('a', 256), 'form.signature_move', 'max'],
            'missing first wrestler' => ['form.wrestlerA', PHP_INT_MAX, 'form.wrestlerA', 'exists'],
            'missing second wrestler' => ['form.wrestlerB', PHP_INT_MAX, 'form.wrestlerB', 'exists'],
            'same wrestler twice' => ['form.wrestlerB', $wrestlerA->id, 'form.wrestlerB', 'different'],
            'missing manager' => ['form.managers', [PHP_INT_MAX], 'form.managers.0', 'exists'],
            'invalid employment date' => ['form.employment_date', 'not-a-date', 'form.employment_date', 'date'],
            default => throw new InvalidArgumentException("Unknown validation case: {$case}"),
        };
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
        $modal->set([
            'form.name' => 'Valid Team',
            'form.wrestlerA' => $wrestlerA->id,
            'form.wrestlerB' => $wrestlerB->id,
        ]);
        $modal->set($field, $value);
        $modal->call('save');

        $modal->assertHasErrors([$errorField => $rule]);
        expect(TagTeam::query()->doesntExist())->toBeTrue();
    })->with([
        'long name',
        'long signature move',
        'missing first wrestler',
        'missing second wrestler',
        'same wrestler twice',
        'missing manager',
        'invalid employment date',
    ]);

    it('rejects a name or signature move used by another tag team', function (string $field) {
        $wrestlers = Wrestler::factory()->count(2)->create();
        $wrestlerA = $wrestlers->firstOrFail();
        $wrestlerB = $wrestlers->skip(1)->firstOrFail();
        TagTeam::factory()->create(['name' => 'Existing Team', 'signature_move' => 'Existing Move']);
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
        $modal->set([
            'form.name' => 'New Team',
            'form.signature_move' => 'New Move',
            'form.wrestlerA' => $wrestlerA->id,
            'form.wrestlerB' => $wrestlerB->id,
        ]);
        $modal->set($field, $field === 'form.name' ? 'Existing Team' : 'Existing Move');
        $modal->call('save');

        $modal->assertHasErrors([$field => 'unique']);
        expect(TagTeam::query()->whereName('New Team')->doesntExist())->toBeTrue();
    })->with([
        'name' => 'form.name',
        'signature move' => 'form.signature_move',
    ]);

    it('rejects a wrestler who is unavailable for tag team membership', function (string $state) {
        $unavailableWrestler = match ($state) {
            'current tag team' => Wrestler::factory()->onCurrentTagTeam()->create(),
            'injured' => Wrestler::factory()->injured()->create(),
            'suspended' => Wrestler::factory()->suspended()->create(),
            default => throw new InvalidArgumentException("Unknown wrestler state: {$state}"),
        };
        $availableWrestler = Wrestler::factory()->create();
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
        $modal->set([
            'form.name' => 'Invalid Team',
            'form.wrestlerA' => $unavailableWrestler->id,
            'form.wrestlerB' => $availableWrestler->id,
        ]);
        $modal->call('save');

        $modal->assertHasErrors(['form.wrestlerA']);
        expect(TagTeam::query()->whereName('Invalid Team')->doesntExist())->toBeTrue();
    })->with(['current tag team', 'injured', 'suspended']);

    it('allows current members to remain on the tag team being edited', function () {
        $wrestlers = Wrestler::factory()->count(2)->create();
        $tagTeam = TagTeam::factory()->create(['name' => 'The Hart Foundation']);
        $tagTeam->wrestlers()->attach($wrestlers->modelKeys(), ['joined_at' => now()->subYear()]);
        $modal = livewire(FormModal::class);

        $modal->call('openModal', $tagTeam->id);
        $modal->set('form.signature_move', 'Hart Attack');
        $modal->call('save');

        $modal->assertHasNoErrors();
        expect($tagTeam->refresh()->signature_move)->toBe('Hart Attack')
            ->and($tagTeam->currentWrestlers()->pluck('wrestlers.id')->sort()->values()->all())
            ->toBe($wrestlers->modelKeys());
    });

    it('resets edited tag team data when reopening in create mode', function () {
        $wrestlers = Wrestler::factory()->count(2)->create();
        $manager = Manager::factory()->create();
        $tagTeam = TagTeam::factory()->create(['name' => 'Existing Team', 'signature_move' => 'Existing Move']);
        $tagTeam->wrestlers()->attach($wrestlers->modelKeys(), ['joined_at' => now()]);
        $tagTeam->managers()->attach($manager, ['hired_at' => now()]);
        $modal = livewire(FormModal::class);

        $modal->call('openModal', $tagTeam->id);
        $modal->call('openModal');

        $modal
            ->assertSet('form.name', '')
            ->assertSet('form.signature_move', '')
            ->assertSet('form.wrestlerA', null)
            ->assertSet('form.wrestlerB', null)
            ->assertSet('form.managers', [])
            ->assertSet('form.employment_date', '');
    });

    it('generates valid dummy data that can create a tag team', function () {
        Wrestler::factory()->count(5)->create();
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
        $modal->call('fillDummyFields');
        $modal->call('save');

        $modal
            ->assertHasNoErrors()
            ->assertDispatched('form-submitted')
            ->assertSet('isModalOpen', false);
        expect(TagTeam::query()->count())->toBe(1)
            ->and(Wrestler::query()->count())->toBe(5);
    });
});

it('forbids users without administrative access from opening the tag team form', function (string $actor, string $operation) {
    $tagTeam = $operation === 'update' ? TagTeam::factory()->create() : null;

    if ($actor === 'basic user') {
        actingAs(basicUser());
    }

    $modal = livewire(FormModal::class);
    $modal->call('openModal', $tagTeam?->id);

    $modal->assertForbidden();
})->with([
    'guest creating' => ['guest', 'create'],
    'basic user creating' => ['basic user', 'create'],
    'guest updating' => ['guest', 'update'],
    'basic user updating' => ['basic user', 'update'],
]);
