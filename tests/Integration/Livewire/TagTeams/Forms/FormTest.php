<?php

declare(strict_types=1);

use App\Livewire\TagTeams\Forms\Form;
use App\Models\Managers\Manager;
use App\Models\TagTeams\TagTeam;
use App\Models\Users\User;
use App\Models\Wrestlers\Wrestler;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->administrator()->create();
    $this->actingAs($this->admin);
    
    $this->wrestlerA = Wrestler::factory()->create(['first_name' => 'John', 'last_name' => 'Wrestler']);
    $this->wrestlerB = Wrestler::factory()->create(['first_name' => 'Jane', 'last_name' => 'Wrestler']);
    $this->manager = Manager::factory()->create(['first_name' => 'Test', 'last_name' => 'Manager']);
});

describe('Form Validation Rules', function () {
    it('validates required fields', function () {
        $form = Livewire::test(Form::class)
            ->set('name', '')
            ->set('wrestlerA', null)
            ->set('wrestlerB', null)
            ->call('store');

        $form->assertHasErrors([
            'name' => 'required',
            'wrestlerA' => 'required',
            'wrestlerB' => 'required',
        ]);
    });

    it('validates tag team name uniqueness', function () {
        TagTeam::factory()->create(['name' => 'Existing Team']);

        $form = Livewire::test(Form::class)
            ->set('name', 'Existing Team')
            ->set('wrestlerA', $this->wrestlerA->id)
            ->set('wrestlerB', $this->wrestlerB->id)
            ->call('store');

        $form->assertHasErrors(['name' => 'unique']);
    });

    it('validates signature move uniqueness', function () {
        TagTeam::factory()->create(['signature_move' => 'Existing Move']);

        $form = Livewire::test(Form::class)
            ->set('name', 'New Team')
            ->set('signature_move', 'Existing Move')
            ->set('wrestlerA', $this->wrestlerA->id)
            ->set('wrestlerB', $this->wrestlerB->id)
            ->call('store');

        $form->assertHasErrors(['signature_move' => 'unique']);
    });

    it('validates wrestlers are different', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'Test Team')
            ->set('wrestlerA', $this->wrestlerA->id)
            ->set('wrestlerB', $this->wrestlerA->id)
            ->call('store');

        $form->assertHasErrors(['wrestlerB' => 'different']);
    });

    it('validates wrestlers exist', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'Test Team')
            ->set('wrestlerA', 99999)
            ->set('wrestlerB', 99998)
            ->call('store');

        $form->assertHasErrors([
            'wrestlerA' => 'exists',
            'wrestlerB' => 'exists',
        ]);
    });

    it('validates managers exist', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'Test Team')
            ->set('wrestlerA', $this->wrestlerA->id)
            ->set('wrestlerB', $this->wrestlerB->id)
            ->set('managers', [99999])
            ->call('store');

        $form->assertHasErrors(['managers.0' => 'exists']);
    });

    it('allows null signature move', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'Test Team')
            ->set('signature_move', null)
            ->set('wrestlerA', $this->wrestlerA->id)
            ->set('wrestlerB', $this->wrestlerB->id)
            ->call('store');

        $form->assertHasNoErrors(['signature_move']);
    });
});

describe('Form Field Validation', function () {
    it('validates field lengths', function () {
        $longName = str_repeat('a', 256);
        $longMove = str_repeat('a', 256);

        $form = Livewire::test(Form::class)
            ->set('name', $longName)
            ->set('signature_move', $longMove)
            ->set('wrestlerA', $this->wrestlerA->id)
            ->set('wrestlerB', $this->wrestlerB->id)
            ->call('store');

        $form->assertHasErrors([
            'name' => 'max',
            'signature_move' => 'max',
        ]);
    });

    it('accepts valid field lengths', function () {
        $validName = str_repeat('a', 255);
        $validMove = str_repeat('a', 255);

        $form = Livewire::test(Form::class)
            ->set('name', $validName)
            ->set('signature_move', $validMove)
            ->set('wrestlerA', $this->wrestlerA->id)
            ->set('wrestlerB', $this->wrestlerB->id)
            ->call('store');

        $form->assertHasNoErrors(['name', 'signature_move']);
    });

    it('validates employment date format', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'Test Team')
            ->set('wrestlerA', $this->wrestlerA->id)
            ->set('wrestlerB', $this->wrestlerB->id)
            ->set('employment_date', 'invalid-date')
            ->call('store');

        $form->assertHasErrors(['employment_date' => 'date']);
    });

    it('accepts valid employment date format', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'Test Team')
            ->set('wrestlerA', $this->wrestlerA->id)
            ->set('wrestlerB', $this->wrestlerB->id)
            ->set('employment_date', '2023-01-15')
            ->call('store');

        $form->assertHasNoErrors(['employment_date']);
    });
});

describe('Form Store Operations', function () {
    it('can store valid tag team data', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'Test Tag Team')
            ->set('signature_move', 'Double Suplex')
            ->set('wrestlerA', $this->wrestlerA->id)
            ->set('wrestlerB', $this->wrestlerB->id)
            ->call('store');

        $form->assertHasNoErrors();
        $form->assertDispatched('tagTeamCreated');

        $this->assertDatabaseHas('tag_teams', [
            'name' => 'Test Tag Team',
            'signature_move' => 'Double Suplex',
        ]);
    });

    it('stores tag team with wrestler relationships', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'Wrestling Duo')
            ->set('wrestlerA', $this->wrestlerA->id)
            ->set('wrestlerB', $this->wrestlerB->id)
            ->call('store');

        $form->assertHasNoErrors();

        $tagTeam = TagTeam::where('name', 'Wrestling Duo')->first();
        expect($tagTeam->wrestlers)->toHaveCount(2);
        expect($tagTeam->wrestlers->pluck('id'))->toContain($this->wrestlerA->id);
        expect($tagTeam->wrestlers->pluck('id'))->toContain($this->wrestlerB->id);
    });

    it('stores tag team with manager relationships', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'Managed Team')
            ->set('wrestlerA', $this->wrestlerA->id)
            ->set('wrestlerB', $this->wrestlerB->id)
            ->set('managers', [$this->manager->id])
            ->call('store');

        $form->assertHasNoErrors();

        $tagTeam = TagTeam::where('name', 'Managed Team')->first();
        expect($tagTeam->managers)->toHaveCount(1);
        expect($tagTeam->managers->first()->id)->toBe($this->manager->id);
    });

    it('creates employment record when employment date provided', function () {
        $employmentDate = '2023-01-15';
        
        $form = Livewire::test(Form::class)
            ->set('name', 'Employed Team')
            ->set('wrestlerA', $this->wrestlerA->id)
            ->set('wrestlerB', $this->wrestlerB->id)
            ->set('employment_date', $employmentDate)
            ->call('store');

        $form->assertHasNoErrors();

        $tagTeam = TagTeam::where('name', 'Employed Team')->first();
        expect($tagTeam->firstEmployment)->not->toBeNull();
        expect($tagTeam->firstEmployment->started_at->toDateString())->toBe($employmentDate);
    });

    it('does not create employment record when employment date omitted', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'Unemployed Team')
            ->set('wrestlerA', $this->wrestlerA->id)
            ->set('wrestlerB', $this->wrestlerB->id)
            ->call('store');

        $form->assertHasNoErrors();

        $tagTeam = TagTeam::where('name', 'Unemployed Team')->first();
        expect($tagTeam->firstEmployment)->toBeNull();
    });
});

describe('Form Update Operations', function () {
    it('can update existing tag team', function () {
        $tagTeam = TagTeam::factory()->create([
            'name' => 'Original Team',
            'signature_move' => 'Original Move',
        ]);

        $form = Livewire::test(Form::class)
            ->call('setModel', $tagTeam)
            ->set('name', 'Updated Team')
            ->set('signature_move', 'Updated Move')
            ->set('wrestlerA', $this->wrestlerA->id)
            ->set('wrestlerB', $this->wrestlerB->id)
            ->call('update');

        $form->assertHasNoErrors();
        $form->assertDispatched('tagTeamUpdated');

        $this->assertDatabaseHas('tag_teams', [
            'id' => $tagTeam->id,
            'name' => 'Updated Team',
            'signature_move' => 'Updated Move',
        ]);
    });

    it('validates name uniqueness excluding current tag team when updating', function () {
        $tagTeam1 = TagTeam::factory()->create(['name' => 'Team One']);
        $tagTeam2 = TagTeam::factory()->create(['name' => 'Team Two']);

        $form = Livewire::test(Form::class)
            ->call('setModel', $tagTeam2)
            ->set('name', 'Team One')
            ->set('wrestlerA', $this->wrestlerA->id)
            ->set('wrestlerB', $this->wrestlerB->id)
            ->call('update');

        $form->assertHasErrors(['name' => 'unique']);
    });

    it('allows keeping same name when updating', function () {
        $tagTeam = TagTeam::factory()->create(['name' => 'Same Name Team']);

        $form = Livewire::test(Form::class)
            ->call('setModel', $tagTeam)
            ->set('name', 'Same Name Team')
            ->set('signature_move', 'New Move')
            ->set('wrestlerA', $this->wrestlerA->id)
            ->set('wrestlerB', $this->wrestlerB->id)
            ->call('update');

        $form->assertHasNoErrors();
        $form->assertDispatched('tagTeamUpdated');
    });

    it('can update wrestler relationships', function () {
        $tagTeam = TagTeam::factory()->create();
        $newWrestlerA = Wrestler::factory()->create();
        $newWrestlerB = Wrestler::factory()->create();

        $form = Livewire::test(Form::class)
            ->call('setModel', $tagTeam)
            ->set('name', $tagTeam->name)
            ->set('wrestlerA', $newWrestlerA->id)
            ->set('wrestlerB', $newWrestlerB->id)
            ->call('update');

        $form->assertHasNoErrors();

        $tagTeam->refresh();
        expect($tagTeam->wrestlers)->toHaveCount(2);
        expect($tagTeam->wrestlers->pluck('id'))->toContain($newWrestlerA->id);
        expect($tagTeam->wrestlers->pluck('id'))->toContain($newWrestlerB->id);
    });
});

describe('Form State Management', function () {
    it('resets form after successful store', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'Test Team')
            ->set('signature_move', 'Test Move')
            ->set('wrestlerA', $this->wrestlerA->id)
            ->set('wrestlerB', $this->wrestlerB->id)
            ->set('employment_date', '2023-01-15')
            ->call('store');

        $form->assertHasNoErrors();
        $form->assertSet('name', '');
        $form->assertSet('signature_move', '');
        $form->assertSet('wrestlerA', null);
        $form->assertSet('wrestlerB', null);
        $form->assertSet('employment_date', '');
    });

    it('preserves form state when validation fails', function () {
        $form = Livewire::test(Form::class)
            ->set('name', '')
            ->set('signature_move', 'Test Move')
            ->set('wrestlerA', $this->wrestlerA->id)
            ->set('wrestlerB', $this->wrestlerB->id)
            ->call('store');

        $form->assertHasErrors();
        $form->assertSet('name', '');
        $form->assertSet('signature_move', 'Test Move');
        $form->assertSet('wrestlerA', $this->wrestlerA->id);
        $form->assertSet('wrestlerB', $this->wrestlerB->id);
    });

    it('loads existing model data correctly', function () {
        $tagTeam = TagTeam::factory()->create([
            'name' => 'Load Test Team',
            'signature_move' => 'Load Move',
        ]);

        $form = Livewire::test(Form::class)
            ->call('setModel', $tagTeam);

        $form->assertSet('name', 'Load Test Team');
        $form->assertSet('signature_move', 'Load Move');
    });

    it('loads wrestler relationships when editing', function () {
        $tagTeam = TagTeam::factory()->create();
        $tagTeam->wrestlers()->attach([$this->wrestlerA->id, $this->wrestlerB->id]);

        $form = Livewire::test(Form::class)
            ->call('setModel', $tagTeam);

        $form->assertSet('wrestlerA', $this->wrestlerA->id);
        $form->assertSet('wrestlerB', $this->wrestlerB->id);
    });

    it('loads manager relationships when editing', function () {
        $tagTeam = TagTeam::factory()->create();
        $tagTeam->managers()->attach([$this->manager->id]);

        $form = Livewire::test(Form::class)
            ->call('setModel', $tagTeam);

        $form->assertSet('managers', [$this->manager->id]);
    });
});

describe('Form Tag Team Relationships', function () {
    it('handles multiple managers correctly', function () {
        $manager1 = Manager::factory()->create();
        $manager2 = Manager::factory()->create();

        $form = Livewire::test(Form::class)
            ->set('name', 'Multi Manager Team')
            ->set('wrestlerA', $this->wrestlerA->id)
            ->set('wrestlerB', $this->wrestlerB->id)
            ->set('managers', [$manager1->id, $manager2->id])
            ->call('store');

        $form->assertHasNoErrors();

        $tagTeam = TagTeam::where('name', 'Multi Manager Team')->first();
        expect($tagTeam->managers)->toHaveCount(2);
        expect($tagTeam->managers->pluck('id'))->toContain($manager1->id);
        expect($tagTeam->managers->pluck('id'))->toContain($manager2->id);
    });

    it('handles empty managers array', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'No Manager Team')
            ->set('wrestlerA', $this->wrestlerA->id)
            ->set('wrestlerB', $this->wrestlerB->id)
            ->set('managers', [])
            ->call('store');

        $form->assertHasNoErrors();

        $tagTeam = TagTeam::where('name', 'No Manager Team')->first();
        expect($tagTeam->managers)->toHaveCount(0);
    });

    it('syncs wrestler relationships correctly', function () {
        $tagTeam = TagTeam::factory()->create();
        $oldWrestler = Wrestler::factory()->create();
        $tagTeam->wrestlers()->attach($oldWrestler->id);

        $form = Livewire::test(Form::class)
            ->call('setModel', $tagTeam)
            ->set('name', $tagTeam->name)
            ->set('wrestlerA', $this->wrestlerA->id)
            ->set('wrestlerB', $this->wrestlerB->id)
            ->call('update');

        $form->assertHasNoErrors();

        $tagTeam->refresh();
        expect($tagTeam->wrestlers)->toHaveCount(2);
        expect($tagTeam->wrestlers->pluck('id'))->not->toContain($oldWrestler->id);
        expect($tagTeam->wrestlers->pluck('id'))->toContain($this->wrestlerA->id);
        expect($tagTeam->wrestlers->pluck('id'))->toContain($this->wrestlerB->id);
    });
});

describe('Form Wrestling Team Validation', function () {
    it('validates wrestling team naming conventions', function () {
        $teamNames = [
            'The Brothers of Destruction',
            'Hardy Boyz',
            'New Day',
            'Dudley Boyz',
            'Legion of Doom',
        ];

        foreach ($teamNames as $name) {
            $form = Livewire::test(Form::class)
                ->set('name', $name)
                ->set('wrestlerA', $this->wrestlerA->id)
                ->set('wrestlerB', $this->wrestlerB->id)
                ->call('store');

            $form->assertHasNoErrors(['name']);
        }
    });

    it('validates wrestling signature moves', function () {
        $signatureMoves = [
            'Doomsday Device',
            '3D',
            'Whazzup',
            'Twist of Fate',
            'Last Ride',
        ];

        foreach ($signatureMoves as $move) {
            $form = Livewire::test(Form::class)
                ->set('name', "Team for {$move}")
                ->set('signature_move', $move)
                ->set('wrestlerA', $this->wrestlerA->id)
                ->set('wrestlerB', $this->wrestlerB->id)
                ->call('store');

            $form->assertHasNoErrors(['signature_move']);
        }
    });

    it('handles tag team with no signature move', function () {
        $form = Livewire::test(Form::class)
            ->set('name', 'Generic Team')
            ->set('wrestlerA', $this->wrestlerA->id)
            ->set('wrestlerB', $this->wrestlerB->id)
            ->call('store');

        $form->assertHasNoErrors();

        $tagTeam = TagTeam::where('name', 'Generic Team')->first();
        expect($tagTeam->signature_move)->toBeNull();
    });
});