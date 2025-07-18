<?php

declare(strict_types=1);

use App\Livewire\Stables\Forms\CreateEditForm;
use App\Models\Managers\Manager;
use App\Models\Stables\Stable;
use App\Models\TagTeams\TagTeam;
use App\Models\Users\User;
use App\Models\Wrestlers\Wrestler;
use Carbon\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->administrator()->create();
    $this->actingAs($this->admin);
});

describe('Form Validation Rules', function () {
    it('validates required fields', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', '')
            ->set('started_at', '')
            ->call('store');

        $form->assertHasErrors([
            'name' => 'required',
            'started_at' => 'required',
        ]);
    });

    it('validates stable name uniqueness', function () {
        Stable::factory()->create(['name' => 'Existing Stable']);

        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Existing Stable')
            ->set('started_at', '2024-01-01')
            ->call('store');

        $form->assertHasErrors(['name' => 'unique']);
    });

    it('validates started_at date format', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Test Stable')
            ->set('started_at', 'invalid-date')
            ->call('store');

        $form->assertHasErrors(['started_at' => 'date']);
    });

    it('validates ended_at date format', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Test Stable')
            ->set('started_at', '2024-01-01')
            ->set('ended_at', 'invalid-date')
            ->call('store');

        $form->assertHasErrors(['ended_at' => 'date']);
    });

    it('validates ended_at is after started_at', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Test Stable')
            ->set('started_at', '2024-06-01')
            ->set('ended_at', '2024-01-01')
            ->call('store');

        $form->assertHasErrors(['ended_at' => 'after']);
    });

    it('allows ended_at to be null', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Test Stable')
            ->set('started_at', '2024-01-01')
            ->set('ended_at', null)
            ->call('store');

        $form->assertHasNoErrors();
    });

    it('allows ended_at to be after started_at', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Test Stable')
            ->set('started_at', '2024-01-01')
            ->set('ended_at', '2024-06-01')
            ->call('store');

        $form->assertHasNoErrors();
    });
});

describe('Form Field Validation', function () {
    it('validates name maximum length', function () {
        $longName = str_repeat('a', 256);

        $form = Livewire::test(CreateEditForm::class)
            ->set('name', $longName)
            ->set('started_at', '2024-01-01')
            ->call('store');

        $form->assertHasErrors(['name' => 'max']);
    });

    it('accepts valid name length', function () {
        $validName = str_repeat('a', 255);

        $form = Livewire::test(CreateEditForm::class)
            ->set('name', $validName)
            ->set('started_at', '2024-01-01')
            ->call('store');

        $form->assertHasNoErrors();
    });

    it('validates wrestlers array', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Test Stable')
            ->set('started_at', '2024-01-01')
            ->set('wrestlers', 'invalid-array')
            ->call('store');

        $form->assertHasErrors(['wrestlers' => 'array']);
    });

    it('validates tag_teams array', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Test Stable')
            ->set('started_at', '2024-01-01')
            ->set('tag_teams', 'invalid-array')
            ->call('store');

        $form->assertHasErrors(['tag_teams' => 'array']);
    });

    it('validates managers array', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Test Stable')
            ->set('started_at', '2024-01-01')
            ->set('managers', 'invalid-array')
            ->call('store');

        $form->assertHasErrors(['managers' => 'array']);
    });
});

describe('Form Relationship Validation', function () {
    it('validates wrestlers exist', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Test Stable')
            ->set('started_at', '2024-01-01')
            ->set('wrestlers', [999])
            ->call('store');

        $form->assertHasErrors(['wrestlers.0' => 'exists']);
    });

    it('validates tag teams exist', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Test Stable')
            ->set('started_at', '2024-01-01')
            ->set('tag_teams', [999])
            ->call('store');

        $form->assertHasErrors(['tag_teams.0' => 'exists']);
    });

    it('validates managers exist', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Test Stable')
            ->set('started_at', '2024-01-01')
            ->set('managers', [999])
            ->call('store');

        $form->assertHasErrors(['managers.0' => 'exists']);
    });

    it('accepts valid wrestler relationships', function () {
        $wrestler = Wrestler::factory()->create();

        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Test Stable')
            ->set('started_at', '2024-01-01')
            ->set('wrestlers', [$wrestler->id])
            ->call('store');

        $form->assertHasNoErrors();
    });

    it('accepts valid tag team relationships', function () {
        $tagTeam = TagTeam::factory()->create();

        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Test Stable')
            ->set('started_at', '2024-01-01')
            ->set('tag_teams', [$tagTeam->id])
            ->call('store');

        $form->assertHasNoErrors();
    });

    it('accepts valid manager relationships', function () {
        $manager = Manager::factory()->create();

        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Test Stable')
            ->set('started_at', '2024-01-01')
            ->set('managers', [$manager->id])
            ->call('store');

        $form->assertHasNoErrors();
    });
});

describe('Form Store Operations', function () {
    it('can store valid stable data', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'The New World Order')
            ->set('started_at', '2024-01-01')
            ->set('ended_at', '2024-12-31')
            ->call('store');

        $form->assertHasNoErrors();
        $form->assertDispatched('stableCreated');

        $this->assertDatabaseHas('stables', [
            'name' => 'The New World Order',
            'started_at' => '2024-01-01',
            'ended_at' => '2024-12-31',
        ]);
    });

    it('stores stable with minimal required data', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Basic Stable')
            ->set('started_at', '2024-01-01')
            ->call('store');

        $form->assertHasNoErrors();

        $this->assertDatabaseHas('stables', [
            'name' => 'Basic Stable',
            'started_at' => '2024-01-01',
            'ended_at' => null,
        ]);
    });

    it('converts date strings to Carbon instances', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Test Stable')
            ->set('started_at', '2024-01-01')
            ->set('ended_at', '2024-12-31')
            ->call('store');

        $form->assertHasNoErrors();

        $stable = Stable::where('name', 'Test Stable')->first();
        expect($stable->started_at)->toBeInstanceOf(Carbon::class);
        expect($stable->ended_at)->toBeInstanceOf(Carbon::class);
        expect($stable->started_at->toDateString())->toBe('2024-01-01');
        expect($stable->ended_at->toDateString())->toBe('2024-12-31');
    });

    it('can store stable with member relationships', function () {
        $wrestler = Wrestler::factory()->create();
        $tagTeam = TagTeam::factory()->create();
        $manager = Manager::factory()->create();

        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Test Stable')
            ->set('started_at', '2024-01-01')
            ->set('wrestlers', [$wrestler->id])
            ->set('tag_teams', [$tagTeam->id])
            ->set('managers', [$manager->id])
            ->call('store');

        $form->assertHasNoErrors();

        $stable = Stable::where('name', 'Test Stable')->first();
        expect($stable->wrestlers)->toContain($wrestler);
        expect($stable->tagTeams)->toContain($tagTeam);
        expect($stable->managers)->toContain($manager);
    });
});

describe('Form Update Operations', function () {
    it('can update existing stable', function () {
        $stable = Stable::factory()->create([
            'name' => 'Original Stable',
            'started_at' => '2024-01-01',
        ]);

        $form = Livewire::test(CreateEditForm::class)
            ->call('setModel', $stable)
            ->set('name', 'Updated Stable')
            ->set('started_at', '2024-01-02')
            ->set('ended_at', '2024-12-31')
            ->call('update');

        $form->assertHasNoErrors();
        $form->assertDispatched('stableUpdated');

        $this->assertDatabaseHas('stables', [
            'id' => $stable->id,
            'name' => 'Updated Stable',
            'started_at' => '2024-01-02',
            'ended_at' => '2024-12-31',
        ]);
    });

    it('validates uniqueness excluding current stable when updating', function () {
        $stable1 = Stable::factory()->create(['name' => 'Stable One']);
        $stable2 = Stable::factory()->create(['name' => 'Stable Two']);

        $form = Livewire::test(CreateEditForm::class)
            ->call('setModel', $stable2)
            ->set('name', 'Stable One')
            ->set('started_at', '2024-01-01')
            ->call('update');

        $form->assertHasErrors(['name' => 'unique']);
    });

    it('allows keeping same name when updating', function () {
        $stable = Stable::factory()->create([
            'name' => 'Test Stable',
            'started_at' => '2024-01-01',
        ]);

        $form = Livewire::test(CreateEditForm::class)
            ->call('setModel', $stable)
            ->set('name', 'Test Stable')
            ->set('started_at', '2024-01-02')
            ->call('update');

        $form->assertHasNoErrors();
        $form->assertDispatched('stableUpdated');
    });

    it('can update stable member relationships', function () {
        $stable = Stable::factory()->create();
        $wrestler = Wrestler::factory()->create();
        $tagTeam = TagTeam::factory()->create();

        $form = Livewire::test(CreateEditForm::class)
            ->call('setModel', $stable)
            ->set('name', $stable->name)
            ->set('started_at', $stable->started_at->toDateString())
            ->set('wrestlers', [$wrestler->id])
            ->set('tag_teams', [$tagTeam->id])
            ->call('update');

        $form->assertHasNoErrors();

        $stable->refresh();
        expect($stable->wrestlers)->toContain($wrestler);
        expect($stable->tagTeams)->toContain($tagTeam);
    });
});

describe('Form Business Logic', function () {
    it('validates debut date change rules for existing stables', function () {
        $stable = Stable::factory()->withMembers()->create([
            'started_at' => '2023-01-01',
        ]);

        $form = Livewire::test(CreateEditForm::class)
            ->call('setModel', $stable)
            ->set('name', $stable->name)
            ->set('started_at', '2024-01-01')
            ->call('update');

        // Should use CanChangeDebutDate rule
        $form->assertHasNoErrors();
    });

    it('handles activity period management correctly', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Test Stable')
            ->set('started_at', '2024-01-01')
            ->call('store');

        $form->assertHasNoErrors();

        $stable = Stable::where('name', 'Test Stable')->first();
        expect($stable->isActive())->toBeTrue();
    });

    it('handles ended stable correctly', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Ended Stable')
            ->set('started_at', '2024-01-01')
            ->set('ended_at', '2024-06-01')
            ->call('store');

        $form->assertHasNoErrors();

        $stable = Stable::where('name', 'Ended Stable')->first();
        expect($stable->isActive())->toBeFalse();
    });

    it('validates member employment status', function () {
        $unemployedWrestler = Wrestler::factory()->unemployed()->create();

        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Test Stable')
            ->set('started_at', '2024-01-01')
            ->set('wrestlers', [$unemployedWrestler->id])
            ->call('store');

        $form->assertHasErrors(['wrestlers.0']);
    });
});

describe('Form State Management', function () {
    it('resets form after successful store', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Test Stable')
            ->set('started_at', '2024-01-01')
            ->call('store');

        $form->assertHasNoErrors();
        $form->assertSet('name', null);
        $form->assertSet('started_at', null);
        $form->assertSet('ended_at', null);
    });

    it('preserves form state when validation fails', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Test Stable')
            ->set('started_at', 'invalid-date')
            ->call('store');

        $form->assertHasErrors();
        $form->assertSet('name', 'Test Stable');
        $form->assertSet('started_at', 'invalid-date');
    });

    it('loads existing model data correctly', function () {
        $stable = Stable::factory()->create([
            'name' => 'Test Stable',
            'started_at' => '2024-01-01',
            'ended_at' => '2024-12-31',
        ]);

        $form = Livewire::test(CreateEditForm::class)
            ->call('setModel', $stable);

        $form->assertSet('name', 'Test Stable');
        $form->assertSet('started_at', '2024-01-01');
        $form->assertSet('ended_at', '2024-12-31');
    });

    it('resets relationship arrays correctly', function () {
        $wrestler = Wrestler::factory()->create();
        $tagTeam = TagTeam::factory()->create();

        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Test Stable')
            ->set('started_at', '2024-01-01')
            ->set('wrestlers', [$wrestler->id])
            ->set('tag_teams', [$tagTeam->id])
            ->call('store');

        $form->assertHasNoErrors();
        $form->assertSet('wrestlers', []);
        $form->assertSet('tag_teams', []);
        $form->assertSet('managers', []);
    });
});