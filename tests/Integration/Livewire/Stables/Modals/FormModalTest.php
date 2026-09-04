<?php

declare(strict_types=1);

use App\Livewire\Stables\Modals\FormModal;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('authorized stable form interactions', function () {
    beforeEach(function () {
        actingAs(administrator());
    });

    it('renders the stable fields and available member choices', function () {
        Wrestler::factory()->bookable()->create(['name' => 'Ric Flair']);
        TagTeam::factory()->employed()->create(['name' => 'The Andersons']);
        Manager::factory()->create(['first_name' => 'J. J.', 'last_name' => 'Dillon']);

        $modal = livewire(FormModal::class);

        $modal->assertSuccessful();
        $modal->assertViewIs('livewire.stables.modals.form-modal');
        $modal
            ->assertPropertyWired('form.name')
            ->assertPropertyWired('form.started_at')
            ->assertPropertyWired('form.ended_at')
            ->assertPropertyWired('form.wrestlers')
            ->assertPropertyWired('form.tag_teams')
            ->assertSee('Ric Flair')
            ->assertSee('The Andersons')
            ->assertDontSee('J. J. Dillon')
            ->assertDontSeeHtml('wire:model="form.managers"');
    });

    it('opens an empty form for creating a stable', function () {
        $modal = livewire(FormModal::class);

        $modal->call('openModal');

        $modal
            ->assertSet('isModalOpen', true)
            ->assertSet('form.name', '')
            ->assertSet('form.started_at', null)
            ->assertSet('form.ended_at', null)
            ->assertSet('form.wrestlers', [])
            ->assertSet('form.tag_teams', [])
            ->assertSee('Create Stable');
    });

    it('loads an existing stable for editing', function () {
        $wrestler = Wrestler::factory()->bookable()->create();
        $tagTeam = TagTeam::factory()->employed()->create();
        $stable = Stable::factory()->create(['name' => 'The Four Horsemen']);
        $stable->activityPeriods()->create([
            'started_at' => '2024-01-01',
            'ended_at' => '2024-12-31',
        ]);
        $stable->wrestlers()->attach($wrestler, ['joined_at' => '2024-01-01']);
        $stable->tagTeams()->attach($tagTeam, ['joined_at' => '2024-01-01']);
        $modal = livewire(FormModal::class);

        $modal->call('openModal', $stable->id);
        $modal->set('form.name', 'The Four Horsemen');

        $modal
            ->assertSet('isModalOpen', true)
            ->assertSet('form.name', 'The Four Horsemen')
            ->assertSet('form.started_at', '2024-01-01')
            ->assertSet('form.ended_at', '2024-12-31')
            ->assertSet('form.wrestlers', [$wrestler->id])
            ->assertSet('form.tag_teams', [$tagTeam->id])
            ->assertSee('Edit Stable');
    });

    it('propagates a missing stable failure', function () {
        expect(fn () => livewire(FormModal::class)->call('openModal', PHP_INT_MAX))
            ->toThrow(ModelNotFoundException::class);
    });

    it('creates an established stable with mixed membership', function () {
        $wrestler = Wrestler::factory()->bookable()->create();
        $tagTeam = TagTeam::factory()->employed()->create();
        $startedAt = now()->toDateString();
        $endedAt = now()->addYear()->toDateString();
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
        $modal->set([
            'form.name' => 'The Dangerous Alliance',
            'form.started_at' => $startedAt,
            'form.ended_at' => $endedAt,
            'form.wrestlers' => [$wrestler->id],
            'form.tag_teams' => [$tagTeam->id],
        ]);
        $modal->call('save');

        $stable = Stable::query()->whereName('The Dangerous Alliance')->firstOrFail();
        $activityPeriod = $stable->firstActivityPeriod()->firstOrFail();
        expect($activityPeriod->started_at->toDateString())->toBe($startedAt)
            ->and($activityPeriod->ended_at?->toDateString())->toBe($endedAt)
            ->and($stable->currentWrestlers()->pluck('wrestlers.id')->all())->toBe([$wrestler->id])
            ->and($stable->currentTagTeams()->pluck('tag_teams.id')->all())->toBe([$tagTeam->id]);
        $modal
            ->assertHasNoErrors()
            ->assertDispatched('refreshDatatable')
            ->assertDispatched('form-submitted')
            ->assertDispatched('closeModal')
            ->assertSet('isModalOpen', false);
    });

    it('creates an unestablished stable without members', function () {
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
        $modal->set('form.name', 'Future Faction');
        $modal->call('save');

        $stable = Stable::query()->whereName('Future Faction')->firstOrFail();
        expect($stable->activityPeriods()->doesntExist())->toBeTrue()
            ->and($stable->currentWrestlers()->doesntExist())->toBeTrue()
            ->and($stable->currentTagTeams()->doesntExist())->toBeTrue();
        $modal->assertHasNoErrors();
    });

    it('updates a stable while preserving former member history', function () {
        $originalWrestler = Wrestler::factory()->bookable()->create();
        $newWrestler = Wrestler::factory()->bookable()->create();
        $originalTagTeam = TagTeam::factory()->employed()->create();
        $newTagTeam = TagTeam::factory()->employed()->create();
        $stable = Stable::factory()->create(['name' => 'Original Stable']);
        $stable->activityPeriods()->create(['started_at' => now()]);
        $stable->wrestlers()->attach($originalWrestler, ['joined_at' => now()]);
        $stable->tagTeams()->attach($originalTagTeam, ['joined_at' => now()]);
        $modal = livewire(FormModal::class);

        $modal->call('openModal', $stable->id);
        $modal->set([
            'form.name' => 'Updated Stable',
            'form.wrestlers' => [$newWrestler->id],
            'form.tag_teams' => [$newTagTeam->id],
        ]);
        $modal->call('save');

        $stable->refresh();
        expect($stable->name)->toBe('Updated Stable')
            ->and($stable->currentWrestlers()->pluck('wrestlers.id')->all())->toBe([$newWrestler->id])
            ->and($stable->previousWrestlers()->pluck('wrestlers.id')->all())->toBe([$originalWrestler->id])
            ->and($stable->currentTagTeams()->pluck('tag_teams.id')->all())->toBe([$newTagTeam->id])
            ->and($stable->previousTagTeams()->pluck('tag_teams.id')->all())->toBe([$originalTagTeam->id]);
        $modal
            ->assertHasNoErrors()
            ->assertDispatched('refreshDatatable')
            ->assertSet('isModalOpen', false);
    });

    it('rejects changing an active stable start date', function () {
        $wrestlers = Wrestler::factory()->count(2)->bookable()->create();
        $stable = Stable::factory()->create();
        $stable->activityPeriods()->create(['started_at' => '2024-01-15']);
        $stable->wrestlers()->attach($wrestlers->modelKeys(), ['joined_at' => '2024-01-15']);
        $modal = livewire(FormModal::class);

        $modal->call('openModal', $stable->id);
        $modal->set('form.started_at', '2024-02-01');
        $modal->call('save');

        $modal
            ->assertHasErrors(['form.started_at'])
            ->assertSet('isModalOpen', true);
    });

    it('requires a stable name', function () {
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
        $modal->call('save');

        $modal
            ->assertHasErrors(['form.name' => 'required'])
            ->assertNotDispatched('closeModal')
            ->assertSet('isModalOpen', true);
        expect(Stable::query()->doesntExist())->toBeTrue();
    });

    it('rejects invalid stable field values', function (string $case) {
        $wrestlers = Wrestler::factory()->count(3)->bookable()->create();
        [$field, $value, $errorField, $rule] = match ($case) {
            'long name' => ['form.name', str_repeat('a', 256), 'form.name', 'max'],
            'invalid start date' => ['form.started_at', 'not-a-date', 'form.started_at', 'date'],
            'invalid end date' => ['form.ended_at', 'not-a-date', 'form.ended_at', 'date'],
            'end before start' => ['form.ended_at', '2023-12-31', 'form.ended_at', 'after'],
            'missing wrestler' => ['form.wrestlers', [PHP_INT_MAX], 'form.wrestlers.0', 'exists'],
            'missing tag team' => ['form.tag_teams', [PHP_INT_MAX], 'form.tag_teams.0', 'exists'],
            default => throw new InvalidArgumentException("Unknown validation case: {$case}"),
        };
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
        $modal->set([
            'form.name' => 'Valid Stable',
            'form.started_at' => '2024-01-01',
            'form.wrestlers' => $wrestlers->modelKeys(),
        ]);
        $modal->set($field, $value);
        $modal->call('save');

        $modal->assertHasErrors([$errorField => $rule]);
        expect(Stable::query()->doesntExist())->toBeTrue();
    })->with([
        'long name',
        'invalid start date',
        'invalid end date',
        'end before start',
        'missing wrestler',
        'missing tag team',
    ]);

    it('rejects the name of another active stable but permits a deleted stable name', function () {
        Stable::factory()->create(['name' => 'Active Stable']);
        $deletedStable = Stable::factory()->create(['name' => 'Former Stable']);
        $deletedStable->delete();
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
        $modal->set('form.name', 'Active Stable');
        $modal->call('save');

        $modal->assertHasErrors(['form.name' => 'unique']);

        $modal->set('form.name', 'Former Stable');
        $modal->call('save');

        $modal->assertHasNoErrors();
        expect(Stable::query()->whereName('Former Stable')->exists())->toBeTrue();
    });

    it('requires enough members to establish a stable', function () {
        $wrestlers = Wrestler::factory()->count(2)->bookable()->create();
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
        $modal->set([
            'form.name' => 'Undersized Stable',
            'form.started_at' => now()->toDateString(),
            'form.wrestlers' => $wrestlers->modelKeys(),
        ]);
        $modal->call('save');

        $modal->assertHasErrors(['form.started_at']);
        expect(Stable::query()->whereName('Undersized Stable')->doesntExist())->toBeTrue();
    });

    it('rejects unavailable stable members', function (string $case) {
        [$wrestler, $tagTeam] = match ($case) {
            'unemployed wrestler' => [Wrestler::factory()->unemployed()->create(), null],
            'injured wrestler' => [Wrestler::factory()->injured()->create(), null],
            'wrestler in another stable' => [Wrestler::factory()->bookable()->create(), null],
            'tag team in another stable' => [null, TagTeam::factory()->employed()->create()],
            default => throw new InvalidArgumentException("Unknown membership case: {$case}"),
        };

        if ($case === 'wrestler in another stable') {
            Stable::factory()->create()->wrestlers()->attach($wrestler, ['joined_at' => now()]);
        }

        if ($case === 'tag team in another stable') {
            Stable::factory()->create()->tagTeams()->attach($tagTeam, ['joined_at' => now()]);
        }

        $modal = livewire(FormModal::class);
        $modal->call('openModal');
        $modal->set('form.name', 'Invalid Stable');
        $modal->set('form.wrestlers', $wrestler === null ? [] : [$wrestler->id]);
        $modal->set('form.tag_teams', $tagTeam === null ? [] : [$tagTeam->id]);
        $modal->call('save');

        $errorField = $wrestler === null ? 'form.tag_teams.0' : 'form.wrestlers.0';
        $modal->assertHasErrors([$errorField]);
        expect(Stable::query()->whereName('Invalid Stable')->doesntExist())->toBeTrue();
    })->with([
        'unemployed wrestler',
        'injured wrestler',
        'wrestler in another stable',
        'tag team in another stable',
    ]);

    it('rejects a wrestler represented by a selected tag team', function () {
        $tagTeam = TagTeam::factory()->employed()->create();
        $representedWrestler = $tagTeam->currentWrestlers()->firstOrFail();
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
        $modal->set([
            'form.name' => 'Duplicate Representation',
            'form.wrestlers' => [$representedWrestler->id],
            'form.tag_teams' => [$tagTeam->id],
        ]);
        $modal->call('save');

        $modal->assertHasErrors(['form.wrestlers.0']);
        expect(Stable::query()->whereName('Duplicate Representation')->doesntExist())->toBeTrue();
    });

    it('allows an unavailable current member to remain while editing stable details', function () {
        $wrestler = Wrestler::factory()->suspended()->create();
        $stable = Stable::factory()->create(['name' => 'Original Stable']);
        $stable->wrestlers()->attach($wrestler, ['joined_at' => now()->subDay()]);
        $modal = livewire(FormModal::class);

        $modal->call('openModal', $stable->id);
        $modal->set('form.name', 'Renamed Stable');
        $modal->call('save');

        $modal->assertHasNoErrors();
        expect($stable->refresh()->name)->toBe('Renamed Stable')
            ->and($stable->currentWrestlers()->pluck('wrestlers.id')->all())->toBe([$wrestler->id]);
    });

    it('resets edited stable data when reopening in create mode', function () {
        $stable = Stable::factory()->active()->create(['name' => 'Existing Stable']);
        $modal = livewire(FormModal::class);

        $modal->call('openModal', $stable->id);
        $modal->call('openModal');

        $modal
            ->assertSet('form.name', '')
            ->assertSet('form.started_at', null)
            ->assertSet('form.ended_at', null)
            ->assertSet('form.wrestlers', [])
            ->assertSet('form.tag_teams', []);
    });

    it('generates dummy profile data without persisting a stable', function () {
        $modal = livewire(FormModal::class);

        $modal->call('openModal');
        $modal->call('fillDummyFields');

        expect($modal->get('form.name'))->not->toBeEmpty();
        expect(Stable::query()->doesntExist())->toBeTrue();
    });
});

it('forbids users without administrative access from opening the stable form', function (string $actor, string $operation) {
    $stable = $operation === 'update' ? Stable::factory()->create() : null;

    if ($actor === 'basic user') {
        actingAs(basicUser());
    }

    $modal = livewire(FormModal::class);
    $modal->call('openModal', $stable?->id);

    $modal->assertForbidden();
})->with([
    'guest creating' => ['guest', 'create'],
    'basic user creating' => ['basic user', 'create'],
    'guest updating' => ['guest', 'update'],
    'basic user updating' => ['basic user', 'update'],
]);
