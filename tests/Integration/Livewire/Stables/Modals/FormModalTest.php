<?php

declare(strict_types=1);

use App\Livewire\Stables\Forms\CreateEditForm;
use App\Livewire\Stables\Modals\FormModal;
use App\Models\Managers\Manager;
use App\Models\Stables\Stable;
use App\Models\TagTeams\TagTeam;
use App\Models\Users\User;
use App\Models\Wrestlers\Wrestler;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->administrator()->create();
    $this->actingAs($this->admin);
});

describe('FormModal Configuration', function () {
    it('returns correct form class', function () {
        $modal = new FormModal();
        $reflection = new ReflectionClass($modal);
        $method = $reflection->getMethod('getFormClass');
        $method->setAccessible(true);

        expect($method->invoke($modal))->toBe(CreateEditForm::class);
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
        $component = Livewire::test(FormModal::class)
            ->call('createMode');

        $component->assertOk();
    });

    it('can render in edit mode', function () {
        $stable = Stable::factory()->create();

        $component = Livewire::test(FormModal::class)
            ->call('editMode', $stable->id);

        $component->assertOk();
    });

    it('displays correct title in create mode', function () {
        $component = Livewire::test(FormModal::class)
            ->call('createMode');

        $component->assertSee('Create Stable');
    });

    it('displays correct title in edit mode', function () {
        $stable = Stable::factory()->create(['name' => 'Test Stable']);

        $component = Livewire::test(FormModal::class)
            ->call('editMode', $stable->id);

        $component->assertSee('Edit Stable');
    });

    it('presents wrestlers list for selection', function () {
        $wrestler = Wrestler::factory()->create(['name' => 'Test Wrestler']);

        $component = Livewire::test(FormModal::class)
            ->call('createMode');

        $component->assertSee('Test Wrestler');
    });

    it('presents tag teams list for selection', function () {
        $tagTeam = TagTeam::factory()->create(['name' => 'Test Tag Team']);

        $component = Livewire::test(FormModal::class)
            ->call('createMode');

        $component->assertSee('Test Tag Team');
    });

    it('presents managers list for selection', function () {
        $manager = Manager::factory()->create(['name' => 'Test Manager']);

        $component = Livewire::test(FormModal::class)
            ->call('createMode');

        $component->assertSee('Test Manager');
    });
});

describe('FormModal Create Operations', function () {
    it('can create a new stable with valid data', function () {
        $component = Livewire::test(FormModal::class)
            ->call('createMode')
            ->set('form.name', 'The New World Order')
            ->set('form.started_at', '2024-01-01')
            ->call('save');

        $component->assertHasNoErrors();
        $component->assertDispatched('stableCreated');

        $this->assertDatabaseHas('stables', [
            'name' => 'The New World Order',
            'started_at' => '2024-01-01',
        ]);
    });

    it('validates required fields when creating', function () {
        $component = Livewire::test(FormModal::class)
            ->call('createMode')
            ->set('form.name', '')
            ->set('form.started_at', '')
            ->call('save');

        $component->assertHasErrors([
            'form.name' => 'required',
            'form.started_at' => 'required',
        ]);
    });

    it('validates stable name uniqueness', function () {
        Stable::factory()->create(['name' => 'Existing Stable']);

        $component = Livewire::test(FormModal::class)
            ->call('createMode')
            ->set('form.name', 'Existing Stable')
            ->set('form.started_at', '2024-01-01')
            ->call('save');

        $component->assertHasErrors(['form.name']);
    });

    it('validates started_at date format', function () {
        $component = Livewire::test(FormModal::class)
            ->call('createMode')
            ->set('form.name', 'Test Stable')
            ->set('form.started_at', 'invalid-date')
            ->call('save');

        $component->assertHasErrors(['form.started_at']);
    });

    it('validates ended_at is after started_at', function () {
        $component = Livewire::test(FormModal::class)
            ->call('createMode')
            ->set('form.name', 'Test Stable')
            ->set('form.started_at', '2024-01-01')
            ->set('form.ended_at', '2023-12-31')
            ->call('save');

        $component->assertHasErrors(['form.ended_at']);
    });

    it('can create stable with optional fields', function () {
        $component = Livewire::test(FormModal::class)
            ->call('createMode')
            ->set('form.name', 'Test Stable')
            ->set('form.started_at', '2024-01-01')
            ->set('form.ended_at', '2024-12-31')
            ->call('save');

        $component->assertHasNoErrors();
        $component->assertDispatched('stableCreated');

        $this->assertDatabaseHas('stables', [
            'name' => 'Test Stable',
            'started_at' => '2024-01-01',
            'ended_at' => '2024-12-31',
        ]);
    });
});

describe('FormModal Edit Operations', function () {
    it('can edit an existing stable', function () {
        $stable = Stable::factory()->create([
            'name' => 'Original Stable',
            'started_at' => '2024-01-01',
        ]);

        $component = Livewire::test(FormModal::class)
            ->call('editMode', $stable->id)
            ->set('form.name', 'Updated Stable')
            ->set('form.started_at', '2024-01-02')
            ->call('save');

        $component->assertHasNoErrors();
        $component->assertDispatched('stableUpdated');

        $this->assertDatabaseHas('stables', [
            'id' => $stable->id,
            'name' => 'Updated Stable',
            'started_at' => '2024-01-02',
        ]);
    });

    it('loads existing stable data in edit mode', function () {
        $stable = Stable::factory()->create([
            'name' => 'Test Stable',
            'started_at' => '2024-01-01',
        ]);

        $component = Livewire::test(FormModal::class)
            ->call('editMode', $stable->id);

        $component->assertSet('form.name', 'Test Stable');
        $component->assertSet('form.started_at', '2024-01-01');
    });

    it('validates name uniqueness excluding current stable when editing', function () {
        $stable1 = Stable::factory()->create(['name' => 'Stable One']);
        $stable2 = Stable::factory()->create(['name' => 'Stable Two']);

        $component = Livewire::test(FormModal::class)
            ->call('editMode', $stable2->id)
            ->set('form.name', 'Stable One')
            ->call('save');

        $component->assertHasErrors(['form.name']);
    });

    it('allows keeping same name when editing', function () {
        $stable = Stable::factory()->create([
            'name' => 'Test Stable',
            'started_at' => '2024-01-01',
        ]);

        $component = Livewire::test(FormModal::class)
            ->call('editMode', $stable->id)
            ->set('form.name', 'Test Stable')
            ->set('form.started_at', '2024-01-02')
            ->call('save');

        $component->assertHasNoErrors();
        $component->assertDispatched('stableUpdated');
    });

    it('validates debut date change rules for existing stables', function () {
        $stable = Stable::factory()->withMembers()->create();

        $component = Livewire::test(FormModal::class)
            ->call('editMode', $stable->id)
            ->set('form.started_at', '2025-01-01')
            ->call('save');

        // Should use CanChangeDebutDate rule
        $component->assertHasNoErrors();
    });
});

describe('FormModal Activity Period Management', function () {
    it('handles activity periods correctly', function () {
        $component = Livewire::test(FormModal::class)
            ->call('createMode')
            ->set('form.name', 'Test Stable')
            ->set('form.started_at', '2024-01-01')
            ->call('save');

        $component->assertHasNoErrors();

        $stable = Stable::where('name', 'Test Stable')->first();
        expect($stable->started_at)->toBeInstanceOf(Carbon::class);
    });

    it('can set ended_at for disbanded stables', function () {
        $component = Livewire::test(FormModal::class)
            ->call('createMode')
            ->set('form.name', 'Disbanded Stable')
            ->set('form.started_at', '2024-01-01')
            ->set('form.ended_at', '2024-06-01')
            ->call('save');

        $component->assertHasNoErrors();

        $stable = Stable::where('name', 'Disbanded Stable')->first();
        expect($stable->ended_at)->toBeInstanceOf(Carbon::class);
    });

    it('validates ended_at is not before started_at', function () {
        $component = Livewire::test(FormModal::class)
            ->call('createMode')
            ->set('form.name', 'Test Stable')
            ->set('form.started_at', '2024-06-01')
            ->set('form.ended_at', '2024-01-01')
            ->call('save');

        $component->assertHasErrors(['form.ended_at']);
    });
});

describe('FormModal Member Management', function () {
    it('can assign wrestlers to stable', function () {
        $wrestler1 = Wrestler::factory()->create();
        $wrestler2 = Wrestler::factory()->create();

        $component = Livewire::test(FormModal::class)
            ->call('createMode')
            ->set('form.name', 'Test Stable')
            ->set('form.started_at', '2024-01-01')
            ->set('form.wrestlers', [$wrestler1->id, $wrestler2->id])
            ->call('save');

        $component->assertHasNoErrors();

        $stable = Stable::where('name', 'Test Stable')->first();
        expect($stable->wrestlers)->toContain($wrestler1);
        expect($stable->wrestlers)->toContain($wrestler2);
    });

    it('can assign tag teams to stable', function () {
        $tagTeam1 = TagTeam::factory()->create();
        $tagTeam2 = TagTeam::factory()->create();

        $component = Livewire::test(FormModal::class)
            ->call('createMode')
            ->set('form.name', 'Test Stable')
            ->set('form.started_at', '2024-01-01')
            ->set('form.tag_teams', [$tagTeam1->id, $tagTeam2->id])
            ->call('save');

        $component->assertHasNoErrors();

        $stable = Stable::where('name', 'Test Stable')->first();
        expect($stable->tagTeams)->toContain($tagTeam1);
        expect($stable->tagTeams)->toContain($tagTeam2);
    });

    it('validates wrestlers exist when assigning', function () {
        $component = Livewire::test(FormModal::class)
            ->call('createMode')
            ->set('form.name', 'Test Stable')
            ->set('form.started_at', '2024-01-01')
            ->set('form.wrestlers', [999])
            ->call('save');

        $component->assertHasErrors(['form.wrestlers']);
    });

    it('validates tag teams exist when assigning', function () {
        $component = Livewire::test(FormModal::class)
            ->call('createMode')
            ->set('form.name', 'Test Stable')
            ->set('form.started_at', '2024-01-01')
            ->set('form.tag_teams', [999])
            ->call('save');

        $component->assertHasErrors(['form.tag_teams']);
    });
});

describe('FormModal State Management', function () {
    it('resets form when switching modes', function () {
        $stable = Stable::factory()->create(['name' => 'Test Stable']);

        $component = Livewire::test(FormModal::class)
            ->call('editMode', $stable->id)
            ->call('createMode');

        $component->assertSet('form.name', null);
        $component->assertSet('form.started_at', null);
        $component->assertSet('form.ended_at', null);
    });

    it('closes modal after successful save', function () {
        $component = Livewire::test(FormModal::class)
            ->call('createMode')
            ->set('form.name', 'Test Stable')
            ->set('form.started_at', '2024-01-01')
            ->call('save');

        $component->assertDispatched('closeModal');
    });

    it('keeps modal open when validation fails', function () {
        $component = Livewire::test(FormModal::class)
            ->call('createMode')
            ->set('form.name', '')
            ->call('save');

        $component->assertNotDispatched('closeModal');
    });
});

describe('FormModal Authorization', function () {
    it('requires authentication', function () {
        auth()->logout();

        $component = Livewire::test(FormModal::class)
            ->call('createMode');

        $component->assertUnauthorized();
    });

    it('requires administrator privileges', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Livewire::test(FormModal::class)
            ->call('createMode');

        $component->assertUnauthorized();
    });
});