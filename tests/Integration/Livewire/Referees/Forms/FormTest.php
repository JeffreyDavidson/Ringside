<?php

declare(strict_types=1);

use App\Livewire\Referees\Forms\CreateEditForm;
use App\Models\Referees\Referee;
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
            ->set('last_name', 'Referee')
            ->set('employment_date', 'invalid-date')
            ->call('store');

        $form->assertHasErrors(['employment_date' => 'date']);
    });

    it('accepts valid employment date format', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('first_name', 'John')
            ->set('last_name', 'Referee')
            ->set('employment_date', '2023-01-15')
            ->call('store');

        $form->assertHasNoErrors(['employment_date']);
    });

    it('allows null employment date', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('first_name', 'John')
            ->set('last_name', 'Referee')
            ->set('employment_date', null)
            ->call('store');

        $form->assertHasNoErrors(['employment_date']);
    });
});

describe('Form Store Operations', function () {
    it('can store valid referee data', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('first_name', 'John')
            ->set('last_name', 'Official')
            ->call('store');

        $form->assertHasNoErrors();
        $form->assertDispatched('refereeCreated');

        $this->assertDatabaseHas('referees', [
            'first_name' => 'John',
            'last_name' => 'Official',
        ]);
    });

    it('stores referee with complete name information', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('first_name', 'Senior')
            ->set('last_name', 'Referee')
            ->set('employment_date', '2023-06-15')
            ->call('store');

        $form->assertHasNoErrors();

        $referee = Referee::where('first_name', 'Senior')
            ->where('last_name', 'Referee')
            ->first();

        expect($referee)->not->toBeNull();
        expect($referee->first_name)->toBe('Senior');
        expect($referee->last_name)->toBe('Referee');
    });

    it('creates employment record when employment date provided', function () {
        $employmentDate = '2023-01-15';
        
        $form = Livewire::test(CreateEditForm::class)
            ->set('first_name', 'Employed')
            ->set('last_name', 'Referee')
            ->set('employment_date', $employmentDate)
            ->call('store');

        $form->assertHasNoErrors();

        $referee = Referee::where('first_name', 'Employed')
            ->where('last_name', 'Referee')
            ->first();

        expect($referee->firstEmployment)->not->toBeNull();
        expect($referee->firstEmployment->started_at->toDateString())->toBe($employmentDate);
    });

    it('does not create employment record when employment date omitted', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('first_name', 'Unemployed')
            ->set('last_name', 'Referee')
            ->call('store');

        $form->assertHasNoErrors();

        $referee = Referee::where('first_name', 'Unemployed')
            ->where('last_name', 'Referee')
            ->first();

        expect($referee->firstEmployment)->toBeNull();
    });
});

describe('Form Update Operations', function () {
    it('can update existing referee', function () {
        $referee = Referee::factory()->create([
            'first_name' => 'Original',
            'last_name' => 'Official',
        ]);

        $form = Livewire::test(CreateEditForm::class)
            ->call('setModel', $referee)
            ->set('first_name', 'Updated')
            ->set('last_name', 'Referee')
            ->call('update');

        $form->assertHasNoErrors();
        $form->assertDispatched('refereeUpdated');

        $this->assertDatabaseHas('referees', [
            'id' => $referee->id,
            'first_name' => 'Updated',
            'last_name' => 'Referee',
        ]);
    });

    it('can update referee names separately', function () {
        $referee = Referee::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $form = Livewire::test(CreateEditForm::class)
            ->call('setModel', $referee)
            ->set('first_name', 'Jane')
            ->set('last_name', 'Doe')
            ->call('update');

        $form->assertHasNoErrors();

        $referee->refresh();
        expect($referee->first_name)->toBe('Jane');
        expect($referee->last_name)->toBe('Doe');
    });

    it('can update employment date', function () {
        $referee = Referee::factory()->create();
        
        $form = Livewire::test(CreateEditForm::class)
            ->call('setModel', $referee)
            ->set('first_name', $referee->first_name)
            ->set('last_name', $referee->last_name)
            ->set('employment_date', '2023-03-01')
            ->call('update');

        $form->assertHasNoErrors();

        $referee->refresh();
        expect($referee->firstEmployment)->not->toBeNull();
        expect($referee->firstEmployment->started_at->toDateString())->toBe('2023-03-01');
    });
});

describe('Form State Management', function () {
    it('resets form after successful store', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('first_name', 'John')
            ->set('last_name', 'Referee')
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
            ->set('last_name', 'Referee')
            ->set('employment_date', '2023-01-15')
            ->call('store');

        $form->assertHasErrors();
        $form->assertSet('first_name', '');
        $form->assertSet('last_name', 'Referee');
        $form->assertSet('employment_date', '2023-01-15');
    });

    it('loads existing model data correctly', function () {
        $referee = Referee::factory()->create([
            'first_name' => 'Load',
            'last_name' => 'Test',
        ]);

        $form = Livewire::test(CreateEditForm::class)
            ->call('setModel', $referee);

        $form->assertSet('first_name', 'Load');
        $form->assertSet('last_name', 'Test');
    });

    it('loads employment date when editing', function () {
        $referee = Referee::factory()->create();
        
        // Create employment record
        $employmentDate = '2023-01-15';
        $referee->employments()->create([
            'started_at' => $employmentDate,
        ]);

        $form = Livewire::test(CreateEditForm::class)
            ->call('setModel', $referee);

        $form->assertSet('employment_date', $employmentDate);
    });
});

describe('Form Referee Official Handling', function () {
    it('handles various official name formats', function () {
        $nameTests = [
            ['John', 'Smith'],
            ['Earl', 'Hebner'],
            ['Mike', 'Chioda'],
            ['Charles', 'Robinson'],
            ['Tim', 'White'],
        ];

        foreach ($nameTests as [$firstName, $lastName]) {
            $form = Livewire::test(CreateEditForm::class)
                ->set('first_name', $firstName)
                ->set('last_name', $lastName)
                ->call('store');

            $form->assertHasNoErrors();
            
            $this->assertDatabaseHas('referees', [
                'first_name' => $firstName,
                'last_name' => $lastName,
            ]);
        }
    });

    it('handles referee names with special characters', function () {
        $nameTests = [
            ['José', 'García'],
            ['Mike', 'O\'Brien'],
            ['Jean', 'Van Der Berg'],
            ['Mary', 'Smith-Jones'],
        ];

        foreach ($nameTests as [$firstName, $lastName]) {
            $form = Livewire::test(CreateEditForm::class)
                ->set('first_name', $firstName)
                ->set('last_name', $lastName)
                ->call('store');

            $form->assertHasNoErrors();
            
            $this->assertDatabaseHas('referees', [
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
        
        $this->assertDatabaseHas('referees', [
            'first_name' => 'A',
            'last_name' => 'B',
        ]);
    });

    it('handles long names within limits', function () {
        $longFirstName = str_repeat('Referee', 35); // 245 chars
        $longLastName = str_repeat('Official', 25); // 200 chars

        $form = Livewire::test(CreateEditForm::class)
            ->set('first_name', $longFirstName)
            ->set('last_name', $longLastName)
            ->call('store');

        $form->assertHasNoErrors();
        
        $this->assertDatabaseHas('referees', [
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

        $referee = Referee::where('first_name', 'Employment')
            ->where('last_name', 'Test')
            ->first();

        expect($referee->employments)->toHaveCount(1);
        expect($referee->firstEmployment->started_at->toDateString())->toBe($employmentDate);
    });

    it('does not create employment without date', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('first_name', 'No Employment')
            ->set('last_name', 'Test')
            ->call('store');

        $form->assertHasNoErrors();

        $referee = Referee::where('first_name', 'No Employment')
            ->where('last_name', 'Test')
            ->first();

        expect($referee->employments)->toHaveCount(0);
    });

    it('loads employment data correctly for editing', function () {
        $referee = Referee::factory()->create();
        $employmentDate = '2023-03-01';
        
        $referee->employments()->create([
            'started_at' => $employmentDate,
        ]);

        $form = Livewire::test(CreateEditForm::class)
            ->call('setModel', $referee);

        $form->assertSet('employment_date', $employmentDate);
    });

    it('handles referee availability tracking', function () {
        $referee = Referee::factory()->create([
            'first_name' => 'Available',
            'last_name' => 'Referee',
        ]);

        // Create employment record
        $referee->employments()->create([
            'started_at' => '2023-01-01',
        ]);

        $form = Livewire::test(CreateEditForm::class)
            ->call('setModel', $referee);

        $form->assertSet('first_name', 'Available');
        $form->assertSet('last_name', 'Referee');
        $form->assertSet('employment_date', '2023-01-01');
    });
});