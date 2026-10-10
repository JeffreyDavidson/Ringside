<?php

declare(strict_types=1);

use App\Actions\Stables\UpdateAction;
use App\Data\Stables\StableData;
use App\Exceptions\Roster\Stables\CannotBeUpdatedException;
use App\Livewire\Stables\Modals\FormModal;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Promotions\PromotionContextService;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('authorized stable form interactions', function () {
    beforeEach(function () {
        actingAs(administrator());
    });

    it('renders the stable fields as searchable selects without embedding the roster', function () {
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
            ->assertSeeHtml('data-roster-combobox="form.wrestlers"')
            ->assertSeeHtml('data-roster-combobox="form.tag_teams"')
            ->assertDontSee('Ric Flair')
            ->assertDontSee('The Andersons')
            ->assertDontSee('J. J. Dillon')
            ->assertDontSeeHtml('<option')
            ->assertDontSeeHtml('wire:model="form.managers"');
    });

    it('shows the current members of an edited stable as selected labels', function () {
        $wrestler = Wrestler::factory()->bookable()->create(['name' => 'Ric Flair']);
        $tagTeam = TagTeam::factory()->employed()->create(['name' => 'The Andersons']);
        Wrestler::factory()->bookable()->create(['name' => 'Unrelated Wrestler']);
        $stable = Stable::factory()->create();
        $stable->activityPeriods()->create(['started_at' => '2024-01-01']);
        $stable->wrestlers()->attach($wrestler, ['joined_at' => '2024-01-01']);
        $stable->tagTeams()->attach($tagTeam, ['joined_at' => '2024-01-01']);

        $modal = livewire(FormModal::class, ['modelId' => $stable->id]);

        $modal
            ->assertSee('Ric Flair')
            ->assertSee('The Andersons')
            ->assertDontSee('Unrelated Wrestler');
    });

    it('searches wrestlers and tag teams for the stable form', function (string $kind, string $term, string $expected) {
        Wrestler::factory()->bookable()->create(['name' => 'Ric Flair']);
        Wrestler::factory()->bookable()->create(['name' => 'Arn Anderson']);
        TagTeam::factory()->employed()->create(['name' => 'The Andersons']);
        $modal = livewire(FormModal::class);

        $options = $modal->instance()->searchRoster($kind, $term);

        expect(array_column($options, 'name'))->toBe([$expected]);
    })->with([
        'wrestlers' => ['wrestlers', 'flair', 'Ric Flair'],
        'tag teams' => ['tag_teams', 'anderson', 'The Andersons'],
    ]);

    it('does not search kinds the stable form does not offer', function () {
        Manager::factory()->create(['first_name' => 'J. J.', 'last_name' => 'Dillon']);
        $modal = livewire(FormModal::class);

        $options = $modal->instance()->searchRoster('managers', '');

        expect($options)->toBe([]);
    });

    it('opens an empty form for creating a stable', function () {
        $modal = livewire(FormModal::class);

        $modal
            ->assertSet('form.name', '')
            ->assertSet('form.started_at', null)
            ->assertSet('form.ended_at', null)
            ->assertSet('form.wrestlers', [])
            ->assertSet('form.tag_teams', [])
            ->assertSee('Add Stable');
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
        $modal = livewire(FormModal::class, ['modelId' => $stable->id]);

        $modal->set('form.name', 'The Four Horsemen');

        $modal
            ->assertSet('form.name', 'The Four Horsemen')
            ->assertSet('form.started_at', '2024-01-01')
            ->assertSet('form.ended_at', '2024-12-31')
            ->assertSet('form.wrestlers', [$wrestler->id])
            ->assertSet('form.tag_teams', [$tagTeam->id])
            ->assertSee('Edit The Four Horsemen');
    });

    it('responds not found when opening a missing stable', function () {
        livewire(FormModal::class, ['modelId' => PHP_INT_MAX])
            ->assertNotFound();
    });

    it('creates an established stable with mixed membership', function () {
        $wrestler = Wrestler::factory()->bookable()->create();
        $tagTeam = TagTeam::factory()->employed()->create();
        $startedAt = now()->toDateString();
        $modal = livewire(FormModal::class);

        $modal->set([
            'form.name' => 'The Dangerous Alliance',
            'form.started_at' => $startedAt,
            'form.wrestlers' => [$wrestler->id],
            'form.tag_teams' => [$tagTeam->id],
        ]);
        $modal->call('save');

        $stable = Stable::query()->whereName('The Dangerous Alliance')->firstOrFail();
        $activityPeriod = $stable->firstActivityPeriod()->firstOrFail();
        expect($activityPeriod->started_at->toDateString())->toBe($startedAt)
            ->and($activityPeriod->ended_at)->toBeNull()
            ->and($stable->currentWrestlers()->pluck('wrestlers.id')->all())->toBe([$wrestler->id])
            ->and($stable->currentTagTeams()->pluck('tag_teams.id')->all())->toBe([$tagTeam->id]);
        $modal
            ->assertHasNoErrors()
            ->assertDispatched('refreshDatatable')
            ->assertDispatched('closeModal');
    });

    it('creates an unestablished stable without members', function () {
        $modal = livewire(FormModal::class);

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
        $modal = livewire(FormModal::class, ['modelId' => $stable->id]);

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
            ->assertDispatched('closeModal');
    });

    it('rejects changing an active stable start date', function () {
        $wrestlers = Wrestler::factory()->count(2)->bookable()->create();
        $stable = Stable::factory()->create();
        $stable->activityPeriods()->create(['started_at' => '2024-01-15']);
        $stable->wrestlers()->attach($wrestlers->modelKeys(), ['joined_at' => '2024-01-15']);
        $modal = livewire(FormModal::class, ['modelId' => $stable->id]);

        $modal->set('form.started_at', '2024-02-01');
        $modal->call('save');

        $modal
            ->assertHasErrors(['form.started_at'])
            ->assertNotDispatched('closeModal');
    });

    it('rejects an end date when creating a stable', function () {
        // Arrange
        $wrestlers = Wrestler::factory()->count(3)->bookable()->create();
        $modal = livewire(FormModal::class);
        $modal->set([
            'form.name' => 'Ended Before It Began',
            'form.started_at' => '2024-01-01',
            'form.ended_at' => '2024-06-01',
            'form.wrestlers' => $wrestlers->modelKeys(),
        ]);

        // Act
        $modal->call('save');

        // Assert
        $modal
            ->assertHasErrors(['form.ended_at' => 'prohibited'])
            ->assertNotDispatched('closeModal');
        expect(Stable::query()->whereName('Ended Before It Began')->doesntExist())->toBeTrue()
            ->and($wrestlers->firstOrFail()->stables()->doesntExist())->toBeTrue();
    });

    it('rejects an end date that would close an active stable without disbanding it', function () {
        // Arrange
        $stable = Stable::factory()->create(['name' => 'Active Stable']);
        $wrestlers = Wrestler::factory()->count(3)->bookable()->create();
        $stable->activityPeriods()->create(['started_at' => '2024-01-15']);
        $stable->wrestlers()->attach($wrestlers->modelKeys(), ['joined_at' => '2024-01-15']);
        $modal = livewire(FormModal::class, ['modelId' => $stable->id]);
        $modal->set('form.ended_at', '2024-06-01');

        // Act
        $modal->call('save');

        // Assert
        $modal
            ->assertHasErrors(['form.ended_at' => 'prohibited'])
            ->assertNotDispatched('closeModal');
        expect($stable->activityPeriods()->sole()->ended_at)->toBeNull()
            ->and($stable->currentWrestlers()->count())->toBe(3);
    });

    it('rejects an end date when establishing an unformed stable by editing it', function () {
        // Arrange
        $wrestlers = Wrestler::factory()->count(3)->bookable()->create();
        $stable = Stable::factory()->create(['name' => 'Unformed Stable']);
        $modal = livewire(FormModal::class, ['modelId' => $stable->id]);
        $modal->set([
            'form.started_at' => '2024-01-01',
            'form.ended_at' => '2024-06-01',
            'form.wrestlers' => $wrestlers->modelKeys(),
        ]);

        // Act
        $modal->call('save');

        // Assert
        $modal->assertHasErrors(['form.ended_at' => 'prohibited']);
        expect($stable->activityPeriods()->doesntExist())->toBeTrue()
            ->and($stable->currentWrestlers()->doesntExist())->toBeTrue();
    });

    it('rejects a new start date for a disbanded stable with several activity periods without a server error', function (string $newStart) {
        // Arrange
        $stable = Stable::factory()->create(['name' => 'Reunited Stable']);
        $first = $stable->activityPeriods()->create(['started_at' => '2020-01-01', 'ended_at' => '2021-01-01']);
        $second = $stable->activityPeriods()->create(['started_at' => '2022-01-01', 'ended_at' => '2023-01-01']);
        $modal = livewire(FormModal::class, ['modelId' => $stable->id]);
        $modal->set('form.started_at', $newStart);

        // Act
        $modal->call('save');

        // Assert
        $modal->assertOk();
        $modal
            ->assertHasErrors(['form.started_at'])
            ->assertNotDispatched('closeModal');
        expect($first->refresh()->started_at->toDateString())->toBe('2020-01-01')
            ->and($first->ended_at?->toDateString())->toBe('2021-01-01')
            ->and($second->refresh()->started_at->toDateString())->toBe('2022-01-01')
            ->and($second->ended_at?->toDateString())->toBe('2023-01-01');
    })->with([
        'after the first period ends' => ['2021-06-01'],
        'within the first period' => ['2020-06-01'],
    ]);

    it('renames a disbanded stable without requiring members', function () {
        // Arrange
        $stable = Stable::factory()->create(['name' => 'Disbanded Stable']);
        $period = $stable->activityPeriods()->create(['started_at' => '2020-01-01', 'ended_at' => '2021-01-01']);
        $modal = livewire(FormModal::class, ['modelId' => $stable->id]);
        $modal->set('form.name', 'Renamed Disbanded Stable');

        // Act
        $modal->call('save');

        // Assert
        $modal
            ->assertHasNoErrors()
            ->assertDispatched('closeModal');
        expect($stable->refresh()->name)->toBe('Renamed Disbanded Stable')
            ->and($period->refresh()->ended_at?->toDateString())->toBe('2021-01-01')
            ->and($stable->currentWrestlers()->doesntExist())->toBeTrue()
            ->and($stable->currentTagTeams()->doesntExist())->toBeTrue();
    });

    it('does not attach members to a disbanded stable', function () {
        // Arrange
        $stable = Stable::factory()->create(['name' => 'Disbanded Stable']);
        $stable->activityPeriods()->create(['started_at' => '2020-01-01', 'ended_at' => '2021-01-01']);
        $wrestlers = Wrestler::factory()->count(3)->bookable()->create();
        $tagTeam = TagTeam::factory()->employed()->create();
        $modal = livewire(FormModal::class, ['modelId' => $stable->id]);
        $modal->set([
            'form.name' => 'Renamed Disbanded Stable',
            'form.wrestlers' => $wrestlers->modelKeys(),
            'form.tag_teams' => [$tagTeam->id],
        ]);

        // Act
        $modal->call('save');

        // Assert
        $modal
            ->assertHasErrors(['form.wrestlers' => 'prohibited', 'form.tag_teams' => 'prohibited'])
            ->assertNotDispatched('closeModal');
        expect($stable->refresh()->name)->toBe('Disbanded Stable')
            ->and($stable->currentWrestlers()->doesntExist())->toBeTrue()
            ->and($stable->currentTagTeams()->doesntExist())->toBeTrue();
    });

    it('shows a form error and keeps the modal open when the update action rejects the data', function () {
        // Arrange
        $stable = Stable::factory()->create(['name' => 'Original Stable']);
        $failure = CannotBeUpdatedException::startDateLocked($stable);
        app()->instance(UpdateAction::class, new class($failure) extends UpdateAction
        {
            public function __construct(private readonly CannotBeUpdatedException $failure) {}

            #[Override]
            public function handle(Stable $stable, StableData $stableData): never
            {
                throw $this->failure;
            }
        });
        $modal = livewire(FormModal::class, ['modelId' => $stable->id]);
        $modal->set('form.name', 'Renamed Stable');

        // Act
        $modal->call('save');

        // Assert
        $modal
            ->assertHasErrors(['form.started_at'])
            ->assertNotDispatched('closeModal')
            ->assertNotDispatched('refreshDatatable');
    });

    it('shows the name as taken and keeps the modal open when a concurrent save wins the promotion name', function () {
        // Arrange
        $promotion = Promotion::factory()->create();
        $context = app(PromotionContextService::class);
        $context->set($promotion);
        $context->enforce();
        Stable::creating(function () use ($promotion): void {
            Stable::withoutEvents(fn () => Stable::factory()->for($promotion, 'promotion')->create(['name' => 'The Alliance']));
        });
        $modal = livewire(FormModal::class);
        $modal->set('form.name', 'The Alliance');

        // Act
        $modal->call('save');

        // Assert
        $modal
            ->assertHasErrors(['form.name'])
            ->assertHasNoErrors(['form.started_at'])
            ->assertSee("an active stable named 'The Alliance' already exists")
            ->assertNotDispatched('closeModal');
        $context->clear();
    });

    it('shows the name as taken on the name field when a concurrent save wins a rename', function () {
        // Arrange
        $promotion = Promotion::factory()->create();
        $context = app(PromotionContextService::class);
        $context->set($promotion);
        $context->enforce();
        $stable = Stable::factory()->for($promotion, 'promotion')->create(['name' => 'The Corporation']);
        Stable::updating(function () use ($promotion): void {
            Stable::withoutEvents(fn () => Stable::factory()->for($promotion, 'promotion')->create(['name' => 'The Alliance']));
        });
        $modal = livewire(FormModal::class, ['modelId' => $stable->id]);
        $modal->set('form.name', 'The Alliance');

        // Act
        $modal->call('save');

        // Assert
        $modal
            ->assertHasErrors(['form.name'])
            ->assertHasNoErrors(['form.started_at'])
            ->assertSee("an active stable named 'The Alliance' already exists")
            ->assertNotDispatched('closeModal');
        expect($stable->refresh()->name)->toBe('The Corporation');
        $context->clear();
    });

    it('requires a stable name', function () {
        $modal = livewire(FormModal::class);

        $modal->call('save');

        $modal
            ->assertHasErrors(['form.name' => 'required'])
            ->assertNotDispatched('closeModal');
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

    it('uses friendly attribute names in stable validation messages', function (string $field, string $message) {
        // Arrange
        $wrestlers = Wrestler::factory()->count(3)->bookable()->create();
        $modal = livewire(FormModal::class);
        $modal->set([
            'form.name' => 'Valid Stable',
            'form.started_at' => '2024-01-01',
            'form.wrestlers' => $wrestlers->modelKeys(),
        ]);

        // Act
        $modal->set($field, 'not-a-date');
        $modal->call('save');

        // Assert
        expect($modal->instance()->getErrorBag()->first($field))->toBe($message);
    })->with([
        'start date' => ['form.started_at', 'The start date field must be a valid date.'],
        'end date' => ['form.ended_at', 'The end date field must be a valid date.'],
    ]);

    it('rejects the name of another active stable but permits a deleted stable name', function () {
        Stable::factory()->create(['name' => 'Active Stable']);
        $deletedStable = Stable::factory()->create(['name' => 'Former Stable']);
        $deletedStable->delete();
        $modal = livewire(FormModal::class);

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
        $modal = livewire(FormModal::class, ['modelId' => $stable->id]);

        $modal->set('form.name', 'Renamed Stable');
        $modal->call('save');

        $modal->assertHasNoErrors();
        expect($stable->refresh()->name)->toBe('Renamed Stable')
            ->and($stable->currentWrestlers()->pluck('wrestlers.id')->all())->toBe([$wrestler->id]);
    });

    it('generates dummy profile data without persisting a stable', function () {
        $modal = livewire(FormModal::class);

        $modal->call('fillDummyFields');

        expect($modal->get('form.name'))->not->toBeEmpty()
            ->and(Stable::query()->doesntExist())->toBeTrue();
    });
});

it('forbids users without administrative access from opening the stable form', function (string $actor, string $operation, int $status) {
    $stable = $operation === 'update' ? Stable::factory()->create() : null;

    if ($actor === 'basic user') {
        actingAs(basicUser());
    }

    livewire(FormModal::class, ['modelId' => $stable?->id])
        ->assertStatus($status);
})->with([
    'guest creating' => ['guest', 'create', 403],
    'basic user creating' => ['basic user', 'create', 403],
    'guest updating' => ['guest', 'update', 403],
    'basic user updating' => ['basic user', 'update', 404],
]);
