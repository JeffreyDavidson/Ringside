<?php

declare(strict_types=1);

use App\Livewire\Titles\Forms\CreateEditForm;
use App\Livewire\Titles\Modals\FormModal;
use App\Models\Titles\Title;
use App\Models\Users\User;
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

        expect($method->invoke($modal))->toBe(Title::class);
    });
});

describe('FormModal Rendering', function () {
    it('can render in create mode', function () {
        $component = Livewire::test(FormModal::class)
            ->call('openModal');

        $component->assertOk();
    });

    it('can render in edit mode', function () {
        $title = Title::factory()->create();

        $component = Livewire::test(FormModal::class)
            ->call('openModal', $title->id);

        $component->assertOk();
    });

    it('displays correct title in create mode', function () {
        $component = Livewire::test(FormModal::class)
            ->call('openModal');

        $component->assertSee('Create Title');
    });

    it('displays correct title in edit mode', function () {
        $title = Title::factory()->create(['name' => 'Test Championship']);

        $component = Livewire::test(FormModal::class)
            ->call('openModal', $title->id);

        $component->assertSee('Edit Title');
    });
});

describe('FormModal Create Operations', function () {
    it('can create a new title with valid data', function () {
        $component = Livewire::test(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'New Championship')
            ->set('form.type', 'Singles')
            ->set('form.introduced_at', '2024-01-01')
            ->call('save');

        $component->assertHasNoErrors();
        $component->assertDispatched('titleCreated');

        $this->assertDatabaseHas('titles', [
            'name' => 'New Championship',
            'type' => 'Singles',
        ]);
    });

    it('validates required fields when creating', function () {
        $component = Livewire::test(FormModal::class)
            ->call('openModal')
            ->set('form.name', '')
            ->set('form.type', '')
            ->call('save');

        $component->assertHasErrors([
            'form.name' => 'required',
            'form.type' => 'required',
        ]);
    });

    it('validates title name uniqueness', function () {
        Title::factory()->create(['name' => 'Existing Championship']);

        $component = Livewire::test(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'Existing Championship')
            ->set('form.type', 'Singles')
            ->set('form.introduced_at', '2024-01-01')
            ->call('save');

        $component->assertHasErrors(['form.name']);
    });

    it('validates title type enum values', function () {
        $component = Livewire::test(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'Test Championship')
            ->set('form.type', 'InvalidType')
            ->call('save');

        $component->assertHasErrors(['form.type']);
    });

    it('validates introduced_at date format', function () {
        $component = Livewire::test(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'Test Championship')
            ->set('form.type', 'Singles')
            ->set('form.introduced_at', 'invalid-date')
            ->call('save');

        $component->assertHasErrors(['form.introduced_at']);
    });
});

describe('FormModal Edit Operations', function () {
    it('can edit an existing title', function () {
        $title = Title::factory()->create([
            'name' => 'Original Championship',
            'type' => 'Singles',
        ]);

        $component = Livewire::test(FormModal::class)
            ->call('openModal', $title->id)
            ->set('form.name', 'Updated Championship')
            ->set('form.type', 'Tag Team')
            ->call('save');

        $component->assertHasNoErrors();
        $component->assertDispatched('titleUpdated');

        $this->assertDatabaseHas('titles', [
            'id' => $title->id,
            'name' => 'Updated Championship',
            'type' => 'Tag Team',
        ]);
    });

    it('loads existing title data in edit mode', function () {
        $title = Title::factory()->create([
            'name' => 'Test Championship',
            'type' => 'Singles',
        ]);

        $component = Livewire::test(FormModal::class)
            ->call('openModal', $title->id);

        $component->assertSet('form.name', 'Test Championship');
        $component->assertSet('form.type', 'Singles');
    });

    it('validates uniqueness excluding current title when editing', function () {
        $title1 = Title::factory()->create(['name' => 'Championship One']);
        $title2 = Title::factory()->create(['name' => 'Championship Two']);

        $component = Livewire::test(FormModal::class)
            ->call('openModal', $title2->id)
            ->set('form.name', 'Championship One')
            ->call('save');

        $component->assertHasErrors(['form.name']);
    });

    it('allows keeping same name when editing', function () {
        $title = Title::factory()->create([
            'name' => 'Test Championship',
            'type' => 'Singles',
        ]);

        $component = Livewire::test(FormModal::class)
            ->call('openModal', $title->id)
            ->set('form.name', 'Test Championship')
            ->set('form.type', 'Tag Team')
            ->call('save');

        $component->assertHasNoErrors();
        $component->assertDispatched('titleUpdated');
    });
});

describe('FormModal State Management', function () {
    it('resets form when switching modes', function () {
        $title = Title::factory()->create(['name' => 'Test Championship']);

        $component = Livewire::test(FormModal::class)
            ->call('openModal', $title->id)
            ->call('openModal');

        $component->assertSet('form.name', null);
        $component->assertSet('form.type', null);
    });

    it('closes modal after successful save', function () {
        $component = Livewire::test(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'New Championship')
            ->set('form.type', 'Singles')
            ->set('form.introduced_at', '2024-01-01')
            ->call('save');

        $component->assertDispatched('closeModal');
    });

    it('keeps modal open when validation fails', function () {
        $component = Livewire::test(FormModal::class)
            ->call('openModal')
            ->set('form.name', '')
            ->call('save');

        $component->assertNotDispatched('closeModal');
    });
});

describe('FormModal Business Logic', function () {
    it('handles title activation periods correctly', function () {
        $component = Livewire::test(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'New Championship')
            ->set('form.type', 'Singles')
            ->set('form.introduced_at', '2024-01-01')
            ->call('save');

        $component->assertHasNoErrors();

        $title = Title::where('name', 'New Championship')->first();
        expect($title->introduced_at)->toBeInstanceOf(Carbon::class);
    });

    it('validates debut date change rules', function () {
        $title = Title::factory()->withActivationPeriod()->create();

        $component = Livewire::test(FormModal::class)
            ->call('openModal', $title->id)
            ->set('form.introduced_at', '2025-01-01')
            ->call('save');

        // Should validate debut date change rules
        $component->assertHasNoErrors();
    });
});

describe('FormModal Authorization', function () {
    it('requires authentication', function () {
        auth()->logout();

        $component = Livewire::test(FormModal::class)
            ->call('openModal');

        $component->assertUnauthorized();
    });

    it('requires administrator privileges', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Livewire::test(FormModal::class)
            ->call('openModal');

        $component->assertUnauthorized();
    });
});