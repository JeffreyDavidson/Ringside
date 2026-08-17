<?php

declare(strict_types=1);

use App\Livewire\Wrestlers\Tables\PreviousManagers;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Roster\Wrestlers\WrestlerManager;
use App\Models\Users\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->admin = User::factory()->administrator()->create();
    $this->wrestler = Wrestler::factory()->create();
    $this->manager = Manager::factory()->create();
    $this->actingAs($this->admin);
});

describe('PreviousManagers Configuration', function () {
    it('requires wrestler id to be set', function () {
        expect(fn () => (new PreviousManagers())->builder())
            ->toThrow(LogicException::class, 'A wrestler was not provided.');
    });

    it('can set wrestler id', function () {
        $component = livewire(PreviousManagers::class, ['wrestlerId' => $this->wrestler->id]);

        $component->assertSet('wrestlerId', $this->wrestler->id);
    });

    it('has correct database table name', function () {
        $component = livewire(PreviousManagers::class, ['wrestlerId' => $this->wrestler->id]);

        $component->assertSet('databaseTableName', 'wrestlers_managers');
    });

    it('defines the manager history columns', function () {
        $component = livewire(PreviousManagers::class, ['wrestlerId' => $this->wrestler->id]);

        $fields = collect(app(PreviousManagers::class)->columns())
            ->map->getField()
            ->all();

        expect($fields)->toBe([
            'manager.full_name',
            'hired_at',
            'fired_at',
        ]);
    });
});

describe('PreviousManagers Query Building', function () {
    it('builds query correctly with wrestler id', function () {
        $component = livewire(PreviousManagers::class, ['wrestlerId' => $this->wrestler->id]);

        $builder = tap(app(PreviousManagers::class), fn (PreviousManagers $table) => $table->wrestlerId = $this->wrestler->id)->builder();

        expect($builder->getModel())->toBeInstanceOf(WrestlerManager::class);
        expect($builder->toSql())->toContain('and "wrestler_id" = ?');
        expect($builder->toSql())->toContain('"fired_at" is not null');
        expect($builder->toSql())->toContain('order by "hired_at" desc');
    });

    it('filters by wrestler id correctly', function () {
        $otherWrestler = Wrestler::factory()->create();

        // Create manager relationships for both wrestlers
        WrestlerManager::create([
            'wrestler_id' => $this->wrestler->id,
            'manager_id' => $this->manager->id,
            'hired_at' => now()->subMonths(6),
            'fired_at' => now()->subMonths(2),
        ]);

        WrestlerManager::create([
            'wrestler_id' => $otherWrestler->id,
            'manager_id' => $this->manager->id,
            'hired_at' => now()->subMonths(4),
            'fired_at' => now()->subMonth(),
        ]);

        $component = livewire(PreviousManagers::class, ['wrestlerId' => $this->wrestler->id]);

        $results = tap(app(PreviousManagers::class), fn (PreviousManagers $table) => $table->wrestlerId = $this->wrestler->id)->builder()->get();

        expect($results)->toHaveCount(1);
        expect($results->firstOrFail()->wrestler_id)->toBe($this->wrestler->id);
    });

    it('only shows relationships that have ended', function () {
        // Create current relationship (no fired_at)
        WrestlerManager::create([
            'wrestler_id' => $this->wrestler->id,
            'manager_id' => $this->manager->id,
            'hired_at' => now()->subMonths(3),
            'fired_at' => null,
        ]);

        // Create previous relationship (with fired_at)
        $previousManager = Manager::factory()->create();
        WrestlerManager::create([
            'wrestler_id' => $this->wrestler->id,
            'manager_id' => $previousManager->id,
            'hired_at' => now()->subMonths(6),
            'fired_at' => now()->subMonths(2),
        ]);

        $component = livewire(PreviousManagers::class, ['wrestlerId' => $this->wrestler->id]);

        $results = tap(app(PreviousManagers::class), fn (PreviousManagers $table) => $table->wrestlerId = $this->wrestler->id)->builder()->get();

        expect($results)->toHaveCount(1);
        expect($results->firstOrFail()->manager_id)->toBe($previousManager->id);
        expect($results->firstOrFail()->fired_at)->not->toBeNull();
    });

    it('orders by hired_at descending', function () {
        $manager2 = Manager::factory()->create();
        $manager3 = Manager::factory()->create();

        // Create relationships in different order
        WrestlerManager::create([
            'wrestler_id' => $this->wrestler->id,
            'manager_id' => $this->manager->id,
            'hired_at' => now()->subMonths(6),
            'fired_at' => now()->subMonths(4),
        ]);

        WrestlerManager::create([
            'wrestler_id' => $this->wrestler->id,
            'manager_id' => $manager2->id,
            'hired_at' => now()->subMonths(3),
            'fired_at' => now()->subMonth(),
        ]);

        WrestlerManager::create([
            'wrestler_id' => $this->wrestler->id,
            'manager_id' => $manager3->id,
            'hired_at' => now()->subMonths(9),
            'fired_at' => now()->subMonths(7),
        ]);

        $component = livewire(PreviousManagers::class, ['wrestlerId' => $this->wrestler->id]);

        $results = tap(app(PreviousManagers::class), fn (PreviousManagers $table) => $table->wrestlerId = $this->wrestler->id)->builder()->get();

        expect($results)->toHaveCount(3);
        // Should be ordered by hired_at desc (most recent first)
        expect($results->firstOrFail()->manager_id)->toBe($manager2->id);
        expect($results->slice(1)->firstOrFail()->manager_id)->toBe($this->manager->id);
        expect($results->reverse()->firstOrFail()->manager_id)->toBe($manager3->id);
    });
});

describe('PreviousManagers Rendering', function () {
    it('can render with previous manager relationships', function () {
        WrestlerManager::create([
            'wrestler_id' => $this->wrestler->id,
            'manager_id' => $this->manager->id,
            'hired_at' => now()->subMonths(6),
            'fired_at' => now()->subMonths(2),
        ]);

        $component = livewire(PreviousManagers::class, ['wrestlerId' => $this->wrestler->id]);

        $component->assertSuccessful();
    });

    it('can render with no previous manager relationships', function () {
        $component = livewire(PreviousManagers::class, ['wrestlerId' => $this->wrestler->id]);

        $component->assertSuccessful();
    });

    it('handles multiple previous manager relationships', function () {
        $manager2 = Manager::factory()->create();
        $manager3 = Manager::factory()->create();

        WrestlerManager::create([
            'wrestler_id' => $this->wrestler->id,
            'manager_id' => $this->manager->id,
            'hired_at' => now()->subMonths(6),
            'fired_at' => now()->subMonths(4),
        ]);

        WrestlerManager::create([
            'wrestler_id' => $this->wrestler->id,
            'manager_id' => $manager2->id,
            'hired_at' => now()->subMonths(3),
            'fired_at' => now()->subMonth(),
        ]);

        WrestlerManager::create([
            'wrestler_id' => $this->wrestler->id,
            'manager_id' => $manager3->id,
            'hired_at' => now()->subMonths(9),
            'fired_at' => now()->subMonths(7),
        ]);

        $component = livewire(PreviousManagers::class, ['wrestlerId' => $this->wrestler->id]);

        $results = tap(app(PreviousManagers::class), fn (PreviousManagers $table) => $table->wrestlerId = $this->wrestler->id)->builder()->get();
        expect($results)->toHaveCount(3);

        $component->assertSuccessful();
    });
});
