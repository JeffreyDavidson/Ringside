<?php

declare(strict_types=1);

use App\Enums\Titles\TitleType;
use App\Livewire\Titles\Forms\CreateEditForm;
use App\Models\Titles\Title;
use App\Models\Users\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->administrator()->create();
    $this->actingAs($this->admin);
});

describe('Form Validation Rules', function () {
    it('validates required fields', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', '')
            ->set('type', '')
            ->call('store');

        $form->assertHasErrors([
            'name' => 'required',
            'type' => 'required',
        ]);
    });

    it('validates title name ends with Title or Titles', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Championship Belt')
            ->set('type', TitleType::Singles->value)
            ->call('store');

        $form->assertHasErrors(['name' => 'ends_with']);
    });

    it('accepts valid title names ending with Title', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Heavyweight Championship Title')
            ->set('type', TitleType::Singles->value)
            ->call('store');

        $form->assertHasNoErrors(['name']);
    });

    it('accepts valid title names ending with Titles', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Tag Team Titles')
            ->set('type', TitleType::TagTeam->value)
            ->call('store');

        $form->assertHasNoErrors(['name']);
    });

    it('validates title name uniqueness', function () {
        Title::factory()->create(['name' => 'Existing Championship Title']);

        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Existing Championship Title')
            ->set('type', TitleType::Singles->value)
            ->call('store');

        $form->assertHasErrors(['name' => 'unique']);
    });

    it('validates title type enum values', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Test Championship Title')
            ->set('type', 'InvalidType')
            ->call('store');

        $form->assertHasErrors(['type' => 'Illuminate\Validation\Rules\Enum']);
    });

    it('accepts valid title type values', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Singles Championship Title')
            ->set('type', TitleType::Singles->value)
            ->call('store');

        $form->assertHasNoErrors(['type']);

        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Tag Team Championship Titles')
            ->set('type', TitleType::TagTeam->value)
            ->call('store');

        $form->assertHasNoErrors(['type']);
    });
});

describe('Form Field Validation', function () {
    it('validates title name length', function () {
        $longName = str_repeat('a', 251) . 'Title';

        $form = Livewire::test(CreateEditForm::class)
            ->set('name', $longName)
            ->set('type', TitleType::Singles->value)
            ->call('store');

        $form->assertHasErrors(['name' => 'max']);
    });

    it('accepts valid title name length', function () {
        $validName = str_repeat('a', 250) . 'Title';

        $form = Livewire::test(CreateEditForm::class)
            ->set('name', $validName)
            ->set('type', TitleType::Singles->value)
            ->call('store');

        $form->assertHasNoErrors(['name']);
    });

    it('validates start date format', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Date Test Title')
            ->set('type', TitleType::Singles->value)
            ->set('start_date', 'invalid-date')
            ->call('store');

        $form->assertHasErrors(['start_date' => 'date']);
    });

    it('accepts valid start date format', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Date Valid Title')
            ->set('type', TitleType::Singles->value)
            ->set('start_date', '2023-01-15')
            ->call('store');

        $form->assertHasNoErrors(['start_date']);
    });

    it('allows null start date', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Null Date Title')
            ->set('type', TitleType::Singles->value)
            ->set('start_date', null)
            ->call('store');

        $form->assertHasNoErrors(['start_date']);
    });
});

describe('Form Store Operations', function () {
    it('can store valid title data', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Test Championship Title')
            ->set('type', TitleType::Singles->value)
            ->call('store');

        $form->assertHasNoErrors();
        $form->assertDispatched('titleCreated');

        $this->assertDatabaseHas('titles', [
            'name' => 'Test Championship Title',
            'type' => TitleType::Singles->value,
        ]);
    });

    it('stores title with all fields', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Complete Championship Title')
            ->set('type', TitleType::TagTeam->value)
            ->set('start_date', '2023-06-15')
            ->call('store');

        $form->assertHasNoErrors();

        $title = Title::where('name', 'Complete Championship Title')->first();
        expect($title)->not->toBeNull();
        expect($title->name)->toBe('Complete Championship Title');
        expect($title->type)->toBe(TitleType::TagTeam);
    });

    it('stores title with proper enum casting', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Enum Test Title')
            ->set('type', TitleType::Singles->value)
            ->call('store');

        $form->assertHasNoErrors();

        $title = Title::where('name', 'Enum Test Title')->first();
        expect($title->type)->toBeInstanceOf(TitleType::class);
        expect($title->type)->toBe(TitleType::Singles);
    });

    it('creates activity period when start date provided', function () {
        $startDate = '2023-01-15';
        
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Activity Test Title')
            ->set('type', TitleType::Singles->value)
            ->set('start_date', $startDate)
            ->call('store');

        $form->assertHasNoErrors();

        $title = Title::where('name', 'Activity Test Title')->first();
        expect($title->firstActivityPeriod)->not->toBeNull();
        expect($title->firstActivityPeriod->started_at->toDateString())->toBe($startDate);
    });

    it('does not create activity period when start date omitted', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'No Activity Test Title')
            ->set('type', TitleType::Singles->value)
            ->call('store');

        $form->assertHasNoErrors();

        $title = Title::where('name', 'No Activity Test Title')->first();
        expect($title->firstActivityPeriod)->toBeNull();
    });
});

describe('Form Update Operations', function () {
    it('can update existing title', function () {
        $title = Title::factory()->create([
            'name' => 'Original Title',
            'type' => TitleType::Singles,
        ]);

        $form = Livewire::test(CreateEditForm::class)
            ->call('setModel', $title)
            ->set('name', 'Updated Championship Title')
            ->set('type', TitleType::TagTeam->value)
            ->call('update');

        $form->assertHasNoErrors();
        $form->assertDispatched('titleUpdated');

        $this->assertDatabaseHas('titles', [
            'id' => $title->id,
            'name' => 'Updated Championship Title',
            'type' => TitleType::TagTeam->value,
        ]);
    });

    it('validates name uniqueness excluding current title when updating', function () {
        $title1 = Title::factory()->create(['name' => 'Title One']);
        $title2 = Title::factory()->create(['name' => 'Title Two']);

        $form = Livewire::test(CreateEditForm::class)
            ->call('setModel', $title2)
            ->set('name', 'Title One')
            ->set('type', $title2->type->value)
            ->call('update');

        $form->assertHasErrors(['name' => 'unique']);
    });

    it('allows keeping same name when updating', function () {
        $title = Title::factory()->create([
            'name' => 'Same Name Title',
            'type' => TitleType::Singles,
        ]);

        $form = Livewire::test(CreateEditForm::class)
            ->call('setModel', $title)
            ->set('name', 'Same Name Title')
            ->set('type', TitleType::TagTeam->value)
            ->call('update');

        $form->assertHasNoErrors();
        $form->assertDispatched('titleUpdated');
    });

    it('can update title type', function () {
        $title = Title::factory()->create([
            'name' => 'Type Change Title',
            'type' => TitleType::Singles,
        ]);

        $form = Livewire::test(CreateEditForm::class)
            ->call('setModel', $title)
            ->set('name', 'Type Change Title')
            ->set('type', TitleType::TagTeam->value)
            ->call('update');

        $form->assertHasNoErrors();

        $title->refresh();
        expect($title->type)->toBe(TitleType::TagTeam);
    });
});

describe('Form State Management', function () {
    it('resets form after successful store', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Reset Test Title')
            ->set('type', TitleType::Singles->value)
            ->set('start_date', '2023-01-15')
            ->call('store');

        $form->assertHasNoErrors();
        $form->assertSet('name', '');
        $form->assertSet('type', '');
        $form->assertSet('start_date', '');
    });

    it('preserves form state when validation fails', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Invalid Name')
            ->set('type', TitleType::Singles->value)
            ->set('start_date', '2023-01-15')
            ->call('store');

        $form->assertHasErrors();
        $form->assertSet('name', 'Invalid Name');
        $form->assertSet('type', TitleType::Singles->value);
        $form->assertSet('start_date', '2023-01-15');
    });

    it('loads existing model data correctly', function () {
        $title = Title::factory()->create([
            'name' => 'Load Test Title',
            'type' => TitleType::TagTeam,
        ]);

        $form = Livewire::test(CreateEditForm::class)
            ->call('setModel', $title);

        $form->assertSet('name', 'Load Test Title');
        $form->assertSet('type', TitleType::TagTeam);
    });

    it('loads activity period start date when editing', function () {
        $title = Title::factory()->create([
            'name' => 'Activity Load Title',
            'type' => TitleType::Singles,
        ]);

        // Create activity period
        $startDate = Carbon::parse('2023-01-15');
        $title->activityPeriods()->create([
            'started_at' => $startDate,
        ]);

        $form = Livewire::test(CreateEditForm::class)
            ->call('setModel', $title);

        $form->assertSet('start_date', '2023-01-15');
    });
});

describe('Form Title Naming Conventions', function () {
    it('validates wrestling title naming patterns', function () {
        $validNames = [
            'Heavyweight Championship Title',
            'Intercontinental Title',
            'Tag Team Titles',
            'Women\'s Championship Title',
            'Cruiserweight Title',
            'United States Championship Title',
        ];

        foreach ($validNames as $name) {
            $form = Livewire::test(CreateEditForm::class)
                ->set('name', $name)
                ->set('type', TitleType::Singles->value)
                ->call('store');

            $form->assertHasNoErrors(['name']);
        }
    });

    it('rejects invalid title naming patterns', function () {
        $invalidNames = [
            'Heavyweight Championship',
            'Intercontinental Belt',
            'Tag Team Championship',
            'Women\'s Belt',
            'Cruiserweight Championship',
        ];

        foreach ($invalidNames as $name) {
            $form = Livewire::test(CreateEditForm::class)
                ->set('name', $name)
                ->set('type', TitleType::Singles->value)
                ->call('store');

            $form->assertHasErrors(['name' => 'ends_with']);
        }
    });

    it('matches title type with naming conventions', function () {
        // Singles titles often use "Title"
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Singles Championship Title')
            ->set('type', TitleType::Singles->value)
            ->call('store');

        $form->assertHasNoErrors();

        // Tag team titles often use "Titles"
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Tag Team Championship Titles')
            ->set('type', TitleType::TagTeam->value)
            ->call('store');

        $form->assertHasNoErrors();
    });
});

describe('Form Activity Period Integration', function () {
    it('handles activity period creation on title store', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'Activity Integration Title')
            ->set('type', TitleType::Singles->value)
            ->set('start_date', '2023-06-15')
            ->call('store');

        $form->assertHasNoErrors();

        $title = Title::where('name', 'Activity Integration Title')->first();
        expect($title->activityPeriods)->toHaveCount(1);
        expect($title->firstActivityPeriod->started_at->toDateString())->toBe('2023-06-15');
    });

    it('does not create activity period without start date', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('name', 'No Activity Title')
            ->set('type', TitleType::Singles->value)
            ->call('store');

        $form->assertHasNoErrors();

        $title = Title::where('name', 'No Activity Title')->first();
        expect($title->activityPeriods)->toHaveCount(0);
    });

    it('loads activity period data correctly for editing', function () {
        $title = Title::factory()->create();
        $startDate = Carbon::parse('2023-03-01');
        
        $title->activityPeriods()->create([
            'started_at' => $startDate,
        ]);

        $form = Livewire::test(CreateEditForm::class)
            ->call('setModel', $title);

        $form->assertSet('start_date', '2023-03-01');
    });
});