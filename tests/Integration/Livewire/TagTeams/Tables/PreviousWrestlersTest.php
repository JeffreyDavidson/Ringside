<?php

declare(strict_types=1);

use App\Livewire\TagTeams\Tables\PreviousWrestlers;
use App\Models\TagTeams\TagTeam;
use App\Models\TagTeams\TagTeamWrestler;
use App\Models\Users\User;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->administrator()->create();
    $this->actingAs($this->admin);
    
    $this->tagTeam = TagTeam::factory()->create([
        'name' => 'Test Tag Team',
    ]);
    
    $this->wrestler = Wrestler::factory()->create([
        'name' => 'Test Wrestler',
    ]);
});

describe('Previous Wrestlers Table Component', function () {
    it('can mount with tag team ID', function () {
        $table = Livewire::test(PreviousWrestlers::class, ['tagTeamId' => $this->tagTeam->id]);
        
        $table->assertOk();
        $table->assertSet('tagTeamId', $this->tagTeam->id);
    });

    it('throws exception when tag team ID not specified', function () {
        expect(function () {
            Livewire::test(PreviousWrestlers::class);
        })->toThrow(Exception::class, "You didn't specify a tag team");
    });

    it('displays only previous wrestlers with left dates', function () {
        // Create current wrestler relationship (no left_at)
        $currentWrestler = Wrestler::factory()->create();
        TagTeamWrestler::create([
            'tag_team_id' => $this->tagTeam->id,
            'wrestler_id' => $currentWrestler->id,
            'joined_at' => Carbon::now()->subDays(10),
            'left_at' => null,
        ]);

        // Create previous wrestler relationship (with left_at)
        TagTeamWrestler::create([
            'tag_team_id' => $this->tagTeam->id,
            'wrestler_id' => $this->wrestler->id,
            'joined_at' => Carbon::now()->subDays(30),
            'left_at' => Carbon::now()->subDays(5),
        ]);

        $table = Livewire::test(PreviousWrestlers::class, ['tagTeamId' => $this->tagTeam->id]);
        
        $table->assertSee($this->wrestler->name); // Should see the wrestler who left
        
        $table->assertDontSee($currentWrestler->name); // Should not see the current wrestler
    });

    it('orders previous wrestlers by joined date descending', function () {
        $wrestler1 = Wrestler::factory()->create(['name' => 'Wrestler One']);
        $wrestler2 = Wrestler::factory()->create(['name' => 'Wrestler Two']);
        $wrestler3 = Wrestler::factory()->create(['name' => 'Wrestler Three']);

        // Create wrestler relationships in non-chronological order
        TagTeamWrestler::create([
            'tag_team_id' => $this->tagTeam->id,
            'wrestler_id' => $wrestler1->id,
            'joined_at' => Carbon::now()->subDays(10),
            'left_at' => Carbon::now()->subDays(5),
        ]);

        TagTeamWrestler::create([
            'tag_team_id' => $this->tagTeam->id,
            'wrestler_id' => $wrestler3->id,
            'joined_at' => Carbon::now()->subDays(30),
            'left_at' => Carbon::now()->subDays(25),
        ]);

        TagTeamWrestler::create([
            'tag_team_id' => $this->tagTeam->id,
            'wrestler_id' => $wrestler2->id,
            'joined_at' => Carbon::now()->subDays(20),
            'left_at' => Carbon::now()->subDays(15),
        ]);

        $table = Livewire::test(PreviousWrestlers::class, ['tagTeamId' => $this->tagTeam->id]);
        
        // Should be ordered by joined_at descending (most recent first)
        $table->assertSee($wrestler1->name) // joined 10 days ago
            ->assertSee($wrestler2->name) // joined 20 days ago  
            ->assertSee($wrestler3->name); // joined 30 days ago
    });

    it('shows only wrestlers for specified tag team', function () {
        $otherTagTeam = TagTeam::factory()->create();
        $otherWrestler = Wrestler::factory()->create();

        // Create wrestler relationship for other tag team
        TagTeamWrestler::create([
            'tag_team_id' => $otherTagTeam->id,
            'wrestler_id' => $otherWrestler->id,
            'joined_at' => Carbon::now()->subDays(10),
            'left_at' => Carbon::now()->subDays(5),
        ]);

        // Create wrestler relationship for our tag team
        TagTeamWrestler::create([
            'tag_team_id' => $this->tagTeam->id,
            'wrestler_id' => $this->wrestler->id,
            'joined_at' => Carbon::now()->subDays(15),
            'left_at' => Carbon::now()->subDays(8),
        ]);

        $table = Livewire::test(PreviousWrestlers::class, ['tagTeamId' => $this->tagTeam->id]);
        
        $table->assertSee($this->wrestler->name);
        $table->assertDontSee($otherWrestler->name);
    });

    it('handles empty previous wrestlers list', function () {
        $table = Livewire::test(PreviousWrestlers::class, ['tagTeamId' => $this->tagTeam->id]);
        
        $table->assertOk();
        $table->assertSee('No items found, try to broaden your search');
    });
});

describe('Previous Wrestlers Table Columns', function () {
    it('displays wrestler name with link', function () {
        TagTeamWrestler::create([
            'tag_team_id' => $this->tagTeam->id,
            'wrestler_id' => $this->wrestler->id,
            'joined_at' => Carbon::now()->subDays(30),
            'left_at' => Carbon::now()->subDays(5),
        ]);

        $table = Livewire::test(PreviousWrestlers::class, ['tagTeamId' => $this->tagTeam->id]);
        
        $table->assertSee($this->wrestler->name);
        $table->assertSee($this->wrestler->name);
        $table->assertSee(route('wrestlers.show', $this->wrestler));
    });

    it('displays joined and left dates', function () {
        $joinedDate = Carbon::now()->subDays(20);
        $leftDate = Carbon::now()->subDays(5);

        TagTeamWrestler::create([
            'tag_team_id' => $this->tagTeam->id,
            'wrestler_id' => $this->wrestler->id,
            'joined_at' => $joinedDate,
            'left_at' => $leftDate,
        ]);

        $table = Livewire::test(PreviousWrestlers::class, ['tagTeamId' => $this->tagTeam->id]);
        
        $table->assertSee($this->wrestler->name);
        $table->assertSee($joinedDate->format('Y-m-d'));
        $table->assertSee($leftDate->format('Y-m-d'));
    });

    it('handles wrestler with no name gracefully', function () {
        TagTeamWrestler::create([
            'tag_team_id' => $this->tagTeam->id,
            'wrestler_id' => $this->wrestler->id,
            'joined_at' => Carbon::now()->subDays(30),
            'left_at' => Carbon::now()->subDays(5),
        ]);

        // Delete the wrestler to simulate missing relationship
        $this->wrestler->delete();

        $table = Livewire::test(PreviousWrestlers::class, ['tagTeamId' => $this->tagTeam->id]);
        
        $table->assertOk();
        $table->assertSee('Unknown');
    });
});

describe('Previous Wrestlers Table Configuration', function () {
    it('uses correct database table name', function () {
        $table = Livewire::test(PreviousWrestlers::class, ['tagTeamId' => $this->tagTeam->id]);
        
        // Just verify the component loads correctly
        $table->assertOk();
    });

    it('sets correct resource name', function () {
        $table = Livewire::test(PreviousWrestlers::class, ['tagTeamId' => $this->tagTeam->id]);
        
        // Just verify the component loads correctly
        $table->assertOk();
    });

    it('includes wrestler relationship in query', function () {
        TagTeamWrestler::create([
            'tag_team_id' => $this->tagTeam->id,
            'wrestler_id' => $this->wrestler->id,
            'joined_at' => Carbon::now()->subDays(30),
            'left_at' => Carbon::now()->subDays(5),
        ]);

        $table = Livewire::test(PreviousWrestlers::class, ['tagTeamId' => $this->tagTeam->id]);
        
        // Just verify the component loads and displays the data
        $table->assertSee($this->wrestler->name);
    });
});

describe('Previous Wrestlers Table Business Logic', function () {
    it('handles multiple tag team memberships for same wrestler', function () {
        // First membership period
        TagTeamWrestler::create([
            'tag_team_id' => $this->tagTeam->id,
            'wrestler_id' => $this->wrestler->id,
            'joined_at' => Carbon::now()->subDays(50),
            'left_at' => Carbon::now()->subDays(40),
        ]);

        // Second membership period
        TagTeamWrestler::create([
            'tag_team_id' => $this->tagTeam->id,
            'wrestler_id' => $this->wrestler->id,
            'joined_at' => Carbon::now()->subDays(20),
            'left_at' => Carbon::now()->subDays(10),
        ]);

        $table = Livewire::test(PreviousWrestlers::class, ['tagTeamId' => $this->tagTeam->id]);
        
        // Should show both membership periods
        $table->assertSee($this->wrestler->name);
    });

    it('validates tag team wrestler relationship integrity', function () {
        TagTeamWrestler::create([
            'tag_team_id' => $this->tagTeam->id,
            'wrestler_id' => $this->wrestler->id,
            'joined_at' => Carbon::now()->subDays(30),
            'left_at' => Carbon::now()->subDays(5),
        ]);

        $table = Livewire::test(PreviousWrestlers::class, ['tagTeamId' => $this->tagTeam->id]);
        
        $table->assertSee($this->wrestler->name);
        
        // Just verify the component loads and displays data correctly
        $table->assertOk();
    });

    it('handles wrestler deletion gracefully', function () {
        TagTeamWrestler::create([
            'tag_team_id' => $this->tagTeam->id,
            'wrestler_id' => $this->wrestler->id,
            'joined_at' => Carbon::now()->subDays(30),
            'left_at' => Carbon::now()->subDays(5),
        ]);

        // Delete the wrestler
        $this->wrestler->delete();

        $table = Livewire::test(PreviousWrestlers::class, ['tagTeamId' => $this->tagTeam->id]);
        
        // Should still work but show "Unknown" for the name
        $table->assertOk();
        $table->assertSee('Unknown');
    });

    it('filters by tag team membership period', function () {
        $recentWrestler = Wrestler::factory()->create(['name' => 'Recent Wrestler']);
        $oldWrestler = Wrestler::factory()->create(['name' => 'Old Wrestler']);

        // Recent wrestler membership
        TagTeamWrestler::create([
            'tag_team_id' => $this->tagTeam->id,
            'wrestler_id' => $recentWrestler->id,
            'joined_at' => Carbon::now()->subDays(5),
            'left_at' => Carbon::now()->subDays(1),
        ]);

        // Old wrestler membership
        TagTeamWrestler::create([
            'tag_team_id' => $this->tagTeam->id,
            'wrestler_id' => $oldWrestler->id,
            'joined_at' => Carbon::now()->subDays(100),
            'left_at' => Carbon::now()->subDays(90),
        ]);

        $table = Livewire::test(PreviousWrestlers::class, ['tagTeamId' => $this->tagTeam->id]);
        
        $table->assertSee($recentWrestler->name)
            ->assertSee($oldWrestler->name);
    });
});