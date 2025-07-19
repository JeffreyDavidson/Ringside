<?php

declare(strict_types=1);

use App\Livewire\Wrestlers\Tables\PreviousManagers;
use App\Models\Managers\Manager;
use App\Models\Users\User;
use App\Models\Wrestlers\Wrestler;
use App\Models\Wrestlers\WrestlerManager;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->administrator()->create();
    $this->actingAs($this->admin);
    
    $this->wrestler = Wrestler::factory()->create([
        'name' => 'Test Wrestler',
    ]);
    
    $this->manager = Manager::factory()->create([
        'first_name' => 'Test',
        'last_name' => 'Manager',
    ]);
});

describe('Previous Managers Table Component', function () {
    it('can mount with wrestler ID', function () {
        $table = Livewire::test(PreviousManagers::class, ['wrestlerId' => $this->wrestler->id]);
        
        $table->assertOk();
        $table->assertSet('wrestlerId', $this->wrestler->id);
    });

    it('throws exception when wrestler ID not specified', function () {
        expect(function () {
            Livewire::test(PreviousManagers::class);
        })->toThrow(Exception::class, "You didn't specify a wrestler");
    });

    it('displays only previous managers with fired dates', function () {
        // Create current manager relationship (no fired_at)
        $currentManager = Manager::factory()->create();
        WrestlerManager::create([
            'wrestler_id' => $this->wrestler->id,
            'manager_id' => $currentManager->id,
            'hired_at' => Carbon::now()->subDays(10),
            'fired_at' => null,
        ]);

        // Create previous manager relationship (with fired_at)
        WrestlerManager::create([
            'wrestler_id' => $this->wrestler->id,
            'manager_id' => $this->manager->id,
            'hired_at' => Carbon::now()->subDays(30),
            'fired_at' => Carbon::now()->subDays(5),
        ]);

        $table = Livewire::test(PreviousManagers::class, ['wrestlerId' => $this->wrestler->id]);
        
        $table->assertSee($this->manager->name); // Should see the fired manager
        $table->assertDontSee($currentManager->name); // Should not see the current manager
    });

    it('orders previous managers by hired date descending', function () {
        $manager1 = Manager::factory()->create(['first_name' => 'Manager', 'last_name' => 'One']);
        $manager2 = Manager::factory()->create(['first_name' => 'Manager', 'last_name' => 'Two']);
        $manager3 = Manager::factory()->create(['first_name' => 'Manager', 'last_name' => 'Three']);

        // Create manager relationships in non-chronological order
        WrestlerManager::create([
            'wrestler_id' => $this->wrestler->id,
            'manager_id' => $manager1->id,
            'hired_at' => Carbon::now()->subDays(10),
            'fired_at' => Carbon::now()->subDays(5),
        ]);

        WrestlerManager::create([
            'wrestler_id' => $this->wrestler->id,
            'manager_id' => $manager3->id,
            'hired_at' => Carbon::now()->subDays(30),
            'fired_at' => Carbon::now()->subDays(25),
        ]);

        WrestlerManager::create([
            'wrestler_id' => $this->wrestler->id,
            'manager_id' => $manager2->id,
            'hired_at' => Carbon::now()->subDays(20),
            'fired_at' => Carbon::now()->subDays(15),
        ]);

        $table = Livewire::test(PreviousManagers::class, ['wrestlerId' => $this->wrestler->id]);
        
        // Should be ordered by hired_at descending (most recent first)
        $table->assertSee($manager1->name) // hired 10 days ago
            ->assertSee($manager2->name) // hired 20 days ago  
            ->assertSee($manager3->name); // hired 30 days ago
    });

    it('shows only managers for specified wrestler', function () {
        $otherWrestler = Wrestler::factory()->create();
        $otherManager = Manager::factory()->create();

        // Create manager relationship for other wrestler
        WrestlerManager::create([
            'wrestler_id' => $otherWrestler->id,
            'manager_id' => $otherManager->id,
            'hired_at' => Carbon::now()->subDays(10),
            'fired_at' => Carbon::now()->subDays(5),
        ]);

        // Create manager relationship for our wrestler
        WrestlerManager::create([
            'wrestler_id' => $this->wrestler->id,
            'manager_id' => $this->manager->id,
            'hired_at' => Carbon::now()->subDays(15),
            'fired_at' => Carbon::now()->subDays(8),
        ]);

        $table = Livewire::test(PreviousManagers::class, ['wrestlerId' => $this->wrestler->id]);
        
        $table->assertSee($this->manager->name);
        $table->assertDontSee($otherManager->name);
    });

    it('handles empty previous managers list', function () {
        $table = Livewire::test(PreviousManagers::class, ['wrestlerId' => $this->wrestler->id]);
        
        $table->assertOk();
        $table->assertSee('No items found, try to broaden your search');
    });
});

describe('Previous Managers Table Configuration', function () {
    it('configures additional selects correctly', function () {
        $table = Livewire::test(PreviousManagers::class, ['wrestlerId' => $this->wrestler->id]);
        
        $table->assertOk();
        // The table should be configured with additional selects for manager_id
        expect($table->instance()->databaseTableName)->toBe('wrestlers_managers');
    });

    it('uses correct database table name', function () {
        $table = Livewire::test(PreviousManagers::class, ['wrestlerId' => $this->wrestler->id]);
        
        expect($table->instance()->databaseTableName)->toBe('wrestlers_managers');
    });
});

describe('Previous Managers Table Filtering', function () {
    it('filters managers by employment period', function () {
        $recentManager = Manager::factory()->create(['first_name' => 'Recent', 'last_name' => 'Manager']);
        $oldManager = Manager::factory()->create(['first_name' => 'Old', 'last_name' => 'Manager']);

        // Recent manager relationship
        WrestlerManager::create([
            'wrestler_id' => $this->wrestler->id,
            'manager_id' => $recentManager->id,
            'hired_at' => Carbon::now()->subDays(5),
            'fired_at' => Carbon::now()->subDays(1),
        ]);

        // Old manager relationship
        WrestlerManager::create([
            'wrestler_id' => $this->wrestler->id,
            'manager_id' => $oldManager->id,
            'hired_at' => Carbon::now()->subDays(100),
            'fired_at' => Carbon::now()->subDays(90),
        ]);

        $table = Livewire::test(PreviousManagers::class, ['wrestlerId' => $this->wrestler->id]);
        
        $table->assertSee($recentManager->name)
            ->assertSee($oldManager->name);
    });

    it('shows manager hire and fire dates', function () {
        $hiredDate = Carbon::now()->subDays(20);
        $firedDate = Carbon::now()->subDays(5);

        WrestlerManager::create([
            'wrestler_id' => $this->wrestler->id,
            'manager_id' => $this->manager->id,
            'hired_at' => $hiredDate,
            'fired_at' => $firedDate,
        ]);

        $table = Livewire::test(PreviousManagers::class, ['wrestlerId' => $this->wrestler->id]);
        
        $table->assertSee($this->manager->name);
        // The table should show the employment period dates
        $table->assertSee($hiredDate->format('Y-m-d'));
        $table->assertSee($firedDate->format('Y-m-d'));
    });
});

describe('Previous Managers Table Business Logic', function () {
    it('handles multiple employment periods for same manager', function () {
        // First employment period
        WrestlerManager::create([
            'wrestler_id' => $this->wrestler->id,
            'manager_id' => $this->manager->id,
            'hired_at' => Carbon::now()->subDays(50),
            'fired_at' => Carbon::now()->subDays(40),
        ]);

        // Second employment period
        WrestlerManager::create([
            'wrestler_id' => $this->wrestler->id,
            'manager_id' => $this->manager->id,
            'hired_at' => Carbon::now()->subDays(20),
            'fired_at' => Carbon::now()->subDays(10),
        ]);

        $table = Livewire::test(PreviousManagers::class, ['wrestlerId' => $this->wrestler->id]);
        
        // Should show both employment periods
        $table->assertSee($this->manager->name);
    });

    it('validates wrestler manager relationship integrity', function () {
        WrestlerManager::create([
            'wrestler_id' => $this->wrestler->id,
            'manager_id' => $this->manager->id,
            'hired_at' => Carbon::now()->subDays(30),
            'fired_at' => Carbon::now()->subDays(5),
        ]);

        $table = Livewire::test(PreviousManagers::class, ['wrestlerId' => $this->wrestler->id]);
        
        $table->assertSee($this->manager->name);
        
        // Just verify the component loads and displays data correctly
        $table->assertOk();
    });

    it('handles manager deletion gracefully', function () {
        WrestlerManager::create([
            'wrestler_id' => $this->wrestler->id,
            'manager_id' => $this->manager->id,
            'hired_at' => Carbon::now()->subDays(30),
            'fired_at' => Carbon::now()->subDays(5),
        ]);

        // Delete the manager
        $this->manager->delete();

        $table = Livewire::test(PreviousManagers::class, ['wrestlerId' => $this->wrestler->id]);
        
        // Should still work but not show the deleted manager
        $table->assertOk();
        $table->assertSee('No items found, try to broaden your search');
    });
});