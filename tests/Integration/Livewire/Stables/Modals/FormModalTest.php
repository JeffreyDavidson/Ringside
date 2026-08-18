<?php

declare(strict_types=1);

use App\Livewire\Stables\Forms\CreateEditForm;
use App\Livewire\Stables\Modals\FormModal;
use App\Models\Lifecycle\Employment;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Users\User;
use Illuminate\Support\Carbon;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->admin = User::factory()->administrator()->create();
    $this->actingAs($this->admin);
    $this->minimumWrestlers = Wrestler::factory()
        ->count(3)
        ->has(Employment::factory()->started(Carbon::parse('2020-01-01')), 'employments')
        ->create();
});

describe('FormModal Configuration', function () {
    it('initializes the stable form', function () {
        $component = livewire(FormModal::class);

        expect($component->get('form'))->toBeInstanceOf(CreateEditForm::class);
    });

    it('returns correct model class', function () {
        $modal = new FormModal();
        $reflection = new ReflectionClass($modal);
        $method = $reflection->getMethod('getModelClass');
        $method->setAccessible(true);

        expect($method->invoke($modal))->toBe(Stable::class);
    });
});

describe('FormModal Rendering', function () {
    it('can render in create mode', function () {
        $component = livewire(FormModal::class)
            ->call('openModal');

        $component->assertOk();
    });

    it('can render in edit mode', function () {
        $stable = Stable::factory()->create();

        $component = livewire(FormModal::class)
            ->call('openModal', $stable->id);

        $component->assertOk();
    });

    it('displays correct title in create mode', function () {
        $component = livewire(FormModal::class)
            ->call('openModal');

        $component->assertSee('Create Stable');
    });

    it('displays correct title in edit mode', function () {
        $stable = Stable::factory()->create(['name' => 'Test Stable']);

        $component = livewire(FormModal::class)
            ->call('openModal', $stable->id);

        $component->assertSee('Edit Stable');
    });

    it('presents wrestlers list for selection', function () {
        $wrestler = Wrestler::factory()->create(['name' => 'Test Wrestler']);

        $component = livewire(FormModal::class)
            ->call('openModal');

        $component->assertSee('Test Wrestler');
    });

    it('presents tag teams list for selection', function () {
        $tagTeam = TagTeam::factory()->create(['name' => 'Test Tag Team']);

        $component = livewire(FormModal::class)
            ->call('openModal');

        $component->assertSee('Test Tag Team');
    });

    it('presents managers list for selection', function () {
        $manager = Manager::factory()->create(['first_name' => 'Test', 'last_name' => 'Manager']);

        $component = livewire(FormModal::class)
            ->call('openModal');

        $component->assertSee('Test Manager');
    });
});

describe('FormModal Create Operations', function () {
    it('can create a new stable with valid data', function () {
        $component = livewire(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'The New World Order')
            ->set('form.started_at', '2024-01-01')
            ->set('form.wrestlers', $this->minimumWrestlers->modelKeys())
            ->call('save');

        $component->assertHasNoErrors();
        $component->assertDispatched('form-submitted');

        $this->assertDatabaseHas('stables', [
            'name' => 'The New World Order',
        ]);

        // Check activity period was created correctly
        $stable = Stable::where('name', 'The New World Order')->firstOrFail();
        expect($stable->firstActivityPeriod)->not()->toBeNull();
        expect(requiredModel($stable->firstActivityPeriod)->started_at->toDateString())->toBe('2024-01-01');
    });

    it('validates required fields when creating', function () {
        $component = livewire(FormModal::class)
            ->call('openModal')
            ->set('form.name', '')
            ->call('save');

        $component->assertHasErrors([
            'form.name' => 'required',
        ]);
    });

    it('validates stable name uniqueness', function () {
        Stable::factory()->create(['name' => 'Existing Stable']);

        $component = livewire(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'Existing Stable')
            ->set('form.started_at', '2024-01-01')
            ->set('form.wrestlers', $this->minimumWrestlers->modelKeys())
            ->call('save');

        $component->assertHasErrors(['form.name']);
    });

    it('allows reusing a deleted stable name', function () {
        $stable = Stable::factory()->create(['name' => 'Existing Stable']);
        $stable->delete();

        $component = livewire(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'Existing Stable')
            ->set('form.started_at', '2024-01-01')
            ->set('form.wrestlers', $this->minimumWrestlers->modelKeys())
            ->call('save');

        $component->assertHasNoErrors();
        $component->assertDispatched('form-submitted');
    });

    it('validates started_at date format', function () {
        $component = livewire(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'Test Stable')
            ->set('form.started_at', '2023-13-32')
            ->call('save');

        $component->assertHasErrors(['form.started_at']);
    });

    it('requires the minimum member headcount when establishing a stable', function () {
        $wrestler = Wrestler::factory()->bookable()->create();

        livewire(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'Undersized Stable')
            ->set('form.started_at', now()->toDateString())
            ->set('form.wrestlers', [$wrestler->id])
            ->call('save')
            ->assertHasErrors(['form.started_at']);

        $this->assertDatabaseMissing('stables', ['name' => 'Undersized Stable']);
    });

    it('validates ended_at is after started_at', function () {
        $component = livewire(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'Test Stable')
            ->set('form.started_at', '2024-01-01')
            ->set('form.ended_at', '2023-12-31')
            ->call('save');

        $component->assertHasErrors(['form.ended_at']);
    });

    it('can create stable with optional fields', function () {
        $component = livewire(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'Test Stable')
            ->set('form.started_at', '2024-01-01')
            ->set('form.ended_at', '2024-12-31')
            ->set('form.wrestlers', $this->minimumWrestlers->modelKeys())
            ->call('save');

        $component->assertHasNoErrors();
        $component->assertDispatched('form-submitted');

        $this->assertDatabaseHas('stables', [
            'name' => 'Test Stable',
        ]);

        // Check activity period was created correctly
        $stable = Stable::where('name', 'Test Stable')->firstOrFail();
        expect($stable->firstActivityPeriod)->not()->toBeNull();
        expect(requiredDate(requiredModel($stable->firstActivityPeriod)->started_at)->toDateString())->toBe('2024-01-01');
        expect(requiredDate(requiredModel($stable->firstActivityPeriod)->ended_at)->toDateString())->toBe('2024-12-31');
    });

    it('preserves an end date when creating a future stable activity period', function () {
        $startDate = now()->addYear();
        $endDate = $startDate->copy()->addYear();

        $component = livewire(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'Future Stable')
            ->set('form.started_at', $startDate->toDateString())
            ->set('form.ended_at', $endDate->toDateString())
            ->set('form.wrestlers', $this->minimumWrestlers->modelKeys())
            ->call('save');

        $component->assertHasNoErrors();

        $stable = Stable::where('name', 'Future Stable')->firstOrFail();
        $activityPeriod = requiredModel($stable->firstActivityPeriod);

        expect($activityPeriod->started_at->toDateString())->toBe($startDate->toDateString())
            ->and(requiredDate($activityPeriod->ended_at)->toDateString())->toBe($endDate->toDateString());
    });
});

describe('FormModal Edit Operations', function () {
    it('can edit an existing stable', function () {
        $stable = Stable::factory()->create([
            'name' => 'Original Stable',
        ]);
        $stable->activityPeriods()->create(['started_at' => '2024-01-01']);

        $component = livewire(FormModal::class)
            ->call('openModal', $stable->id)
            ->set('form.name', 'Updated Stable')
            ->set('form.started_at', '2024-01-02')
            ->set('form.wrestlers', $this->minimumWrestlers->modelKeys())
            ->call('save');

        $component->assertHasNoErrors();
        $component->assertDispatched('form-submitted');

        $this->assertDatabaseHas('stables', [
            'id' => $stable->id,
            'name' => 'Updated Stable',
        ]);

        // Check activity period was updated
        expect(requiredModel(freshModel($stable)->firstActivityPeriod)->started_at->toDateString())->toBe('2024-01-02');
    });

    it('preserves an end date when establishing an existing stable in the future', function () {
        $stable = Stable::factory()->create(['name' => 'Unestablished Stable']);
        $startDate = now()->addYear();
        $endDate = $startDate->copy()->addYear();

        $component = livewire(FormModal::class)
            ->call('openModal', $stable->id)
            ->set('form.started_at', $startDate->toDateString())
            ->set('form.ended_at', $endDate->toDateString())
            ->set('form.wrestlers', $this->minimumWrestlers->modelKeys())
            ->call('save');

        $component->assertHasNoErrors();

        $activityPeriod = requiredModel(freshModel($stable)->firstActivityPeriod);

        expect($activityPeriod->started_at->toDateString())->toBe($startDate->toDateString())
            ->and(requiredDate($activityPeriod->ended_at)->toDateString())->toBe($endDate->toDateString());
    });

    it('loads existing stable data in edit mode', function () {
        $stable = Stable::factory()->create([
            'name' => 'Test Stable',
        ]);
        $stable->activityPeriods()->create(['started_at' => '2024-01-01']);

        $component = livewire(FormModal::class)
            ->call('openModal', $stable->id);

        $component->assertSet('form.name', 'Test Stable');
        $component->assertSet('form.started_at', '2024-01-01');
    });

    it('validates name uniqueness excluding current stable when editing', function () {
        $stable1 = Stable::factory()->create(['name' => 'Stable One']);
        $stable2 = Stable::factory()->create(['name' => 'Stable Two']);

        $component = livewire(FormModal::class)
            ->call('openModal', $stable2->id)
            ->set('form.name', 'Stable One')
            ->call('save');

        $component->assertHasErrors(['form.name']);
    });

    it('allows keeping same name when editing', function () {
        $stable = Stable::factory()->create([
            'name' => 'Test Stable',
        ]);
        $stable->activityPeriods()->create(['started_at' => '2024-01-01']);

        $component = livewire(FormModal::class)
            ->call('openModal', $stable->id)
            ->set('form.name', 'Test Stable')
            ->set('form.started_at', '2024-01-02')
            ->set('form.wrestlers', $this->minimumWrestlers->modelKeys())
            ->call('save');

        $component->assertHasNoErrors();
        $component->assertDispatched('form-submitted');
    });

    it('validates debut date change rules for existing stables', function () {
        $stable = Stable::factory()->create();
        // Create some wrestlers and associate them with the stable
        $wrestler1 = Wrestler::factory()->create();
        $wrestler2 = Wrestler::factory()->create();
        $wrestler3 = Wrestler::factory()->create();
        $stable->wrestlers()->attach([
            $wrestler1->id => ['joined_at' => now()],
            $wrestler2->id => ['joined_at' => now()],
            $wrestler3->id => ['joined_at' => now()],
        ]);

        $component = livewire(FormModal::class)
            ->call('openModal', $stable->id)
            ->set('form.started_at', '2025-01-01')
            ->call('save');

        // Should use CanChangeDebutDate rule
        $component->assertHasNoErrors();
    });
});

describe('FormModal Activity Period Management', function () {
    it('handles activity periods correctly', function () {
        $component = livewire(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'Test Stable')
            ->set('form.started_at', '2024-01-01')
            ->set('form.wrestlers', $this->minimumWrestlers->modelKeys())
            ->call('save');

        $component->assertHasNoErrors();

        $stable = Stable::where('name', 'Test Stable')->firstOrFail();
        expect($stable->firstActivityPeriod)->not->toBeNull()->started_at->toBeInstanceOf(Carbon::class);
    });

    it('can set ended_at for disbanded stables', function () {
        $component = livewire(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'Disbanded Stable')
            ->set('form.started_at', '2024-01-01')
            ->set('form.ended_at', '2024-06-01')
            ->set('form.wrestlers', $this->minimumWrestlers->modelKeys())
            ->call('save');

        $component->assertHasNoErrors();

        $stable = Stable::where('name', 'Disbanded Stable')->firstOrFail();
        expect($stable->firstActivityPeriod)->not->toBeNull()->ended_at->toBeInstanceOf(Carbon::class);
    });

    it('validates ended_at is not before started_at', function () {
        $component = livewire(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'Test Stable')
            ->set('form.started_at', '2024-06-01')
            ->set('form.ended_at', '2024-01-01')
            ->call('save');

        $component->assertHasErrors(['form.ended_at']);
    });
});

describe('FormModal Member Management', function () {
    it('can assign wrestlers to stable', function () {
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();
        $wrestler3 = Wrestler::factory()->bookable()->create();

        $component = livewire(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'Test Stable')
            ->set('form.started_at', now()->toDateString())
            ->set('form.wrestlers', [$wrestler1->id, $wrestler2->id, $wrestler3->id])
            ->call('save');

        $component->assertHasNoErrors();

        $stable = Stable::where('name', 'Test Stable')->firstOrFail();
        $stable->refresh();
        expect($stable->wrestlers->pluck('id'))->toContain($wrestler1->id);
        expect($stable->wrestlers->pluck('id'))->toContain($wrestler2->id);
        expect($stable->wrestlers->pluck('id'))->toContain($wrestler3->id);
    });

    it('can assign tag teams to stable', function () {
        $tagTeam1 = TagTeam::factory()->employed()->create();
        $tagTeam2 = TagTeam::factory()->employed()->create();

        $component = livewire(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'Test Stable')
            ->set('form.started_at', now()->toDateString())
            ->set('form.tag_teams', [$tagTeam1->id, $tagTeam2->id])
            ->call('save');

        $component->assertHasNoErrors();

        $stable = Stable::where('name', 'Test Stable')->firstOrFail();
        $stable->refresh();
        expect($stable->tagTeams->pluck('id'))->toContain($tagTeam1->id);
        expect($stable->tagTeams->pluck('id'))->toContain($tagTeam2->id);
    });

    it('rejects a wrestler who currently belongs to another stable', function () {
        $wrestler = Wrestler::factory()->bookable()->create();
        $existingStable = Stable::factory()->create();
        $existingStable->wrestlers()->attach($wrestler, ['joined_at' => now()]);

        $component = livewire(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'Another Stable')
            ->set('form.wrestlers', [$wrestler->id])
            ->call('save');

        $component->assertHasErrors(['form.wrestlers.0']);
        $this->assertDatabaseMissing('stables', ['name' => 'Another Stable']);
    });

    it('rejects a tag team that currently belongs to another stable', function () {
        $tagTeam = TagTeam::factory()->employed()->create();
        $existingStable = Stable::factory()->create();
        $existingStable->tagTeams()->attach($tagTeam, ['joined_at' => now()]);

        $component = livewire(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'Another Stable')
            ->set('form.tag_teams', [$tagTeam->id])
            ->call('save');

        $component->assertHasErrors(['form.tag_teams.0']);
        $this->assertDatabaseMissing('stables', ['name' => 'Another Stable']);
    });

    it('preserves existing members when editing stable details', function () {
        $stable = Stable::factory()->create(['name' => 'Original Stable']);
        $wrestler = Wrestler::factory()->suspended()->create();
        $stable->wrestlers()->attach($wrestler, ['joined_at' => now()->subDay()]);

        $component = livewire(FormModal::class)
            ->call('openModal', $stable->id)
            ->set('form.name', 'Updated Stable')
            ->call('save');

        $component->assertHasNoErrors();

        expect(freshModel($stable)->currentWrestlers()->pluck('wrestlers.id'))
            ->toContain($wrestler->id);
    });

    it('rejects unemployed stable members', function () {
        $wrestler = Wrestler::factory()->unemployed()->create();

        $component = livewire(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'Test Stable')
            ->set('form.wrestlers', [$wrestler->id])
            ->call('save');

        $component->assertHasErrors(['form.wrestlers.0']);
        $this->assertDatabaseMissing('stables', ['name' => 'Test Stable']);
    });

    it('rejects an injured wrestler', function () {
        $wrestler = Wrestler::factory()->injured()->create();

        $component = livewire(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'Test Stable')
            ->set('form.wrestlers', [$wrestler->id])
            ->call('save');

        $component->assertHasErrors(['form.wrestlers.0']);
        $this->assertDatabaseMissing('stables', ['name' => 'Test Stable']);
    });

    it('rejects a wrestler represented by a selected tag team', function () {
        $tagTeam = TagTeam::factory()->employed()->create();
        $wrestler = $tagTeam->currentWrestlers()->firstOrFail();

        $component = livewire(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'Test Stable')
            ->set('form.wrestlers', [$wrestler->id])
            ->set('form.tag_teams', [$tagTeam->id])
            ->call('save');

        $component->assertHasErrors(['form.wrestlers.0']);
        $this->assertDatabaseMissing('stables', ['name' => 'Test Stable']);
    });

    it('rejects an existing stable wrestler represented by a newly selected tag team', function () {
        $tagTeam = TagTeam::factory()->employed()->create();
        $wrestler = $tagTeam->currentWrestlers()->firstOrFail();
        $stable = Stable::factory()->create();
        $stable->wrestlers()->attach($wrestler, ['joined_at' => now()]);

        $component = livewire(FormModal::class)
            ->call('openModal', $stable->id)
            ->set('form.tag_teams', [$tagTeam->id])
            ->call('save');

        $component->assertHasErrors(['form.wrestlers.0']);
        expect(freshModel($stable)->currentTagTeams()->exists())->toBeFalse();
    });

    it('validates wrestlers exist when assigning', function () {
        $component = livewire(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'Test Stable')
            ->set('form.started_at', '2024-01-01')
            ->set('form.wrestlers', [999])
            ->call('save');

        $component->assertHasErrors(['form.wrestlers.0']);
    });

    it('validates tag teams exist when assigning', function () {
        $component = livewire(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'Test Stable')
            ->set('form.started_at', '2024-01-01')
            ->set('form.wrestlers', $this->minimumWrestlers->modelKeys())
            ->set('form.tag_teams', [999])
            ->call('save');

        $component->assertHasErrors(['form.tag_teams.0']);
    });
});

describe('FormModal State Management', function () {
    it('resets form when switching modes', function () {
        $stable = Stable::factory()->create(['name' => 'Test Stable']);

        $component = livewire(FormModal::class)
            ->call('openModal', $stable->id)
            ->call('openModal');

        $component->assertSet('form.name', null);
        $component->assertSet('form.started_at', null);
        $component->assertSet('form.ended_at', null);
    });

    it('closes modal after successful save', function () {
        $component = livewire(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'Test Stable')
            ->set('form.started_at', '2024-01-01')
            ->set('form.wrestlers', $this->minimumWrestlers->modelKeys())
            ->call('save');

        $component->assertDispatched('closeModal');
    });

    it('keeps modal open when validation fails', function () {
        $component = livewire(FormModal::class)
            ->call('openModal')
            ->set('form.name', '')
            ->call('save');

        $component->assertNotDispatched('closeModal');
    });
});

describe('FormModal Authorization', function () {
    it('forbids creating a stable without permission', function () {
        $this->actingAs(User::factory()->create());

        livewire(FormModal::class)
            ->set('form.name', 'Unauthorized Stable')
            ->call('save')
            ->assertForbidden();

        $this->assertDatabaseMissing('stables', ['name' => 'Unauthorized Stable']);
    });

    it('forbids updating a stable without permission', function () {
        $stable = Stable::factory()->create(['name' => 'Original Stable']);
        $this->actingAs(User::factory()->create());

        livewire(FormModal::class, ['modelId' => $stable->id])
            ->set('form.name', 'Unauthorized Rename')
            ->call('save')
            ->assertForbidden();

        expect($stable->refresh()->name)->toBe('Original Stable');
    });
});
