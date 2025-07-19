<?php

declare(strict_types=1);

use App\Livewire\Managers\Forms\CreateEditForm;
use App\Models\Managers\Manager;
use App\Models\Users\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->administrator()->create();
    $this->actingAs($this->admin);
});

describe('Form Validation Rules', function () {
    it('validates required fields', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('first_name', '')
            ->set('last_name', '')
            ->call('store');

        $form->assertHasErrors([
            'first_name' => 'required',
            'last_name' => 'required',
        ]);
    });

    it('validates field lengths', function () {
        $longName = str_repeat('a', 256);

        $form = Livewire::test(CreateEditForm::class)
            ->set('first_name', $longName)
            ->set('last_name', $longName)
            ->call('store');

        $form->assertHasErrors([
            'first_name' => 'max',
            'last_name' => 'max',
        ]);
    });

    it('accepts valid field lengths', function () {
        $validName = str_repeat('a', 255);

        $form = Livewire::test(CreateEditForm::class)
            ->set('first_name', $validName)
            ->set('last_name', $validName)
            ->call('store');

        $form->assertHasNoErrors(['first_name', 'last_name']);
    });

    it('validates employment date format', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('first_name', 'John')
            ->set('last_name', 'Doe')
            ->set('employment_date', 'invalid-date')
            ->call('store');

        $form->assertHasErrors(['employment_date' => 'date']);
    });

    it('accepts valid employment date format', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('first_name', 'John')
            ->set('last_name', 'Doe')
            ->set('employment_date', '2023-01-15')
            ->call('store');

        $form->assertHasNoErrors(['employment_date']);
    });

    it('allows null employment date', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('first_name', 'John')
            ->set('last_name', 'Doe')
            ->set('employment_date', null)
            ->call('store');

        $form->assertHasNoErrors(['employment_date']);
    });
});

describe('Form Store Operations', function () {
    it('can store valid manager data', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('first_name', 'John')
            ->set('last_name', 'Manager')
            ->call('store');

        $form->assertHasNoErrors();
        $form->assertDispatched('managerCreated');

        $this->assertDatabaseHas('managers', [
            'first_name' => 'John',
            'last_name' => 'Manager',
        ]);
    });

    it('stores manager with complete name information', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('first_name', 'Professional')
            ->set('last_name', 'Wrestling')
            ->set('employment_date', '2023-06-15')
            ->call('store');

        $form->assertHasNoErrors();

        $manager = Manager::where('first_name', 'Professional')
            ->where('last_name', 'Wrestling')
            ->first();

        expect($manager)->not()->toBeNull();
        expect($manager->first_name)->toBe('Professional');
        expect($manager->last_name)->toBe('Wrestling');
    });

    it('creates employment record when employment date provided', function () {
        $employmentDate = '2023-01-15';
        
        $form = Livewire::test(CreateEditForm::class)
            ->set('first_name', 'Employed')
            ->set('last_name', 'Manager')
            ->set('employment_date', $employmentDate)
            ->call('store');

        $form->assertHasNoErrors();

        $manager = Manager::where('first_name', 'Employed')
            ->where('last_name', 'Manager')
            ->first();

        expect($manager->firstEmployment)->not()->toBeNull();
        expect($manager->firstEmployment->started_at->toDateString())->toBe($employmentDate);
    });

    it('does not create employment record when employment date omitted', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('first_name', 'Unemployed')
            ->set('last_name', 'Manager')
            ->call('store');

        $form->assertHasNoErrors();

        $manager = Manager::where('first_name', 'Unemployed')
            ->where('last_name', 'Manager')
            ->first();

        expect($manager->firstEmployment)->toBeNull();
    });
});

describe('Form Update Operations', function () {
    it('can update existing manager', function () {
        $manager = Manager::factory()->create([
            'first_name' => 'Original',
            'last_name' => 'Name',
        ]);

        $form = Livewire::test(CreateEditForm::class)
            ->call('setModel', $manager)
            ->set('first_name', 'Updated')
            ->set('last_name', 'Manager')
            ->call('update');

        $form->assertHasNoErrors();
        $form->assertDispatched('managerUpdated');

        $this->assertDatabaseHas('managers', [
            'id' => $manager->id,
            'first_name' => 'Updated',
            'last_name' => 'Manager',
        ]);
    });

    it('can update manager names separately', function () {
        $manager = Manager::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $form = Livewire::test(CreateEditForm::class)
            ->call('setModel', $manager)
            ->set('first_name', 'Jane')
            ->set('last_name', 'Doe')
            ->call('update');

        $form->assertHasNoErrors();

        $manager->refresh();
        expect($manager->first_name)->toBe('Jane');
        expect($manager->last_name)->toBe('Doe');
    });

    it('can update employment date', function () {
        $manager = Manager::factory()->create();
        
        $form = Livewire::test(CreateEditForm::class)
            ->call('setModel', $manager)
            ->set('first_name', $manager->first_name)
            ->set('last_name', $manager->last_name)
            ->set('employment_date', '2023-03-01')
            ->call('update');

        $form->assertHasNoErrors();

        $manager->refresh();
        expect($manager->firstEmployment)->not()->toBeNull();
        expect($manager->firstEmployment->started_at->toDateString())->toBe('2023-03-01');
    });
});

describe('Form State Management', function () {
    it('resets form after successful store', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('first_name', 'John')
            ->set('last_name', 'Manager')
            ->set('employment_date', '2023-01-15')
            ->call('store');

        $form->assertHasNoErrors();
        $form->assertSet('first_name', '');
        $form->assertSet('last_name', '');
        $form->assertSet('employment_date', null);
    });

    it('preserves form state when validation fails', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('first_name', '')
            ->set('last_name', 'Manager')
            ->set('employment_date', '2023-01-15')
            ->call('store');

        $form->assertHasErrors();
        $form->assertSet('first_name', '');
        $form->assertSet('last_name', 'Manager');
        $form->assertSet('employment_date', '2023-01-15');
    });

    it('loads existing model data correctly', function () {
        $manager = Manager::factory()->create([
            'first_name' => 'Load',
            'last_name' => 'Test',
        ]);

        $form = Livewire::test(CreateEditForm::class)
            ->call('setModel', $manager);

        $form->assertSet('first_name', 'Load');
        $form->assertSet('last_name', 'Test');
    });

    it('loads employment date when editing', function () {
        $manager = Manager::factory()->create();
        
        // Create employment record
        $employmentDate = '2023-01-15';
        $manager->employments()->create([
            'started_at' => $employmentDate,
        ]);

        $form = Livewire::test(CreateEditForm::class)
            ->call('setModel', $manager);

        $form->assertSet('employment_date', $employmentDate);
    });
});

describe('Form Manager Name Handling', function () {
    it('handles various name formats', function () {
        $nameTests = [
            ['John', 'Doe'],
            ['Mary', 'Smith-Jones'],
            ['Bob', 'O\'Connor'],
            ['Jean', 'Van Der Berg'],
            ['José', 'García'],
        ];

        foreach ($nameTests as [$firstName, $lastName]) {
            $form = Livewire::test(CreateEditForm::class)
                ->set('first_name', $firstName)
                ->set('last_name', $lastName)
                ->call('store');

            $form->assertHasNoErrors();
            
            $this->assertDatabaseHas('managers', [
                'first_name' => $firstName,
                'last_name' => $lastName,
            ]);
        }
    });

    it('handles single character names', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('first_name', 'A')
            ->set('last_name', 'B')
            ->call('store');

        $form->assertHasNoErrors();
        
        $this->assertDatabaseHas('managers', [
            'first_name' => 'A',
            'last_name' => 'B',
        ]);
    });

    it('handles long names within limits', function () {
        $longFirstName = str_repeat('A', 100);
        $longLastName = str_repeat('B', 100);

        $form = Livewire::test(CreateEditForm::class)
            ->set('first_name', $longFirstName)
            ->set('last_name', $longLastName)
            ->call('store');

        $form->assertHasNoErrors();
        
        $this->assertDatabaseHas('managers', [
            'first_name' => $longFirstName,
            'last_name' => $longLastName,
        ]);
    });
});

describe('Form Employment Integration', function () {
    it('handles employment date creation correctly', function () {
        $employmentDate = '2023-06-15';
        
        $form = Livewire::test(CreateEditForm::class)
            ->set('first_name', 'Employment')
            ->set('last_name', 'Test')
            ->set('employment_date', $employmentDate)
            ->call('store');

        $form->assertHasNoErrors();

        $manager = Manager::where('first_name', 'Employment')
            ->where('last_name', 'Test')
            ->first();

        expect($manager->employments)->toHaveCount(1);
        expect($manager->firstEmployment->started_at->toDateString())->toBe($employmentDate);
    });

    it('does not create employment without date', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('first_name', 'No Employment')
            ->set('last_name', 'Test')
            ->call('store');

        $form->assertHasNoErrors();

        $manager = Manager::where('first_name', 'No Employment')
            ->where('last_name', 'Test')
            ->first();

        expect($manager->employments)->toHaveCount(0);
    });

    it('loads employment data correctly for editing', function () {
        $manager = Manager::factory()->create();
        $employmentDate = '2023-03-01';
        
        $manager->employments()->create([
            'started_at' => $employmentDate,
        ]);

        $form = Livewire::test(CreateEditForm::class)
            ->call('setModel', $manager);

        $form->assertSet('employment_date', $employmentDate);
    });
});