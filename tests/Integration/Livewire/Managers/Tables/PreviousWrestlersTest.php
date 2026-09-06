<?php

declare(strict_types=1);

use App\Livewire\Managers\Tables\PreviousWrestlers;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Roster\Wrestlers\WrestlerManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->manager = Manager::factory()->create();
    actingAs(administrator());
});

describe('PreviousWrestlers configuration', function (): void {
    it('requires a manager', function (): void {
        // Act & Assert
        expect(fn () => (new PreviousWrestlers())->builder())
            ->toThrow(LogicException::class, 'A manager was not provided.');
    });
});

describe('PreviousWrestlers query', function (): void {
    it('returns only ended wrestler assignments for the requested manager in newest-first order', function (): void {
        // Arrange
        $otherManager = Manager::factory()->create();
        $recentWrestler = Wrestler::factory()->create();
        $olderWrestler = Wrestler::factory()->create();
        $currentWrestler = Wrestler::factory()->create();
        $otherWrestler = Wrestler::factory()->create();
        WrestlerManager::query()->create([
            'wrestler_id' => $olderWrestler->id,
            'manager_id' => $this->manager->id,
            'hired_at' => Date::now()->subMonths(3),
            'fired_at' => Date::now()->subMonths(2),
        ]);
        WrestlerManager::query()->create([
            'wrestler_id' => $recentWrestler->id,
            'manager_id' => $this->manager->id,
            'hired_at' => Date::now()->subMonth(),
            'fired_at' => Date::now()->subWeek(),
        ]);
        WrestlerManager::query()->create([
            'wrestler_id' => $currentWrestler->id,
            'manager_id' => $this->manager->id,
            'hired_at' => Date::now()->subDays(3),
            'fired_at' => null,
        ]);
        WrestlerManager::query()->create([
            'wrestler_id' => $otherWrestler->id,
            'manager_id' => $otherManager->id,
            'hired_at' => Date::now()->subDays(2),
            'fired_at' => Date::now()->subDay(),
        ]);
        $table = new PreviousWrestlers();
        $table->managerId = $this->manager->id;

        // Act
        $assignments = $table->builder()->get();

        // Assert
        expect($assignments->pluck('wrestler_id')->all())->toBe([
            $recentWrestler->id,
            $olderWrestler->id,
        ])->and($assignments->every->relationLoaded('wrestler'))->toBeTrue();
    });

    it('omits deleted wrestlers', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()->create();
        WrestlerManager::query()->create([
            'wrestler_id' => $wrestler->id,
            'manager_id' => $this->manager->id,
            'hired_at' => Date::now()->subMonth(),
            'fired_at' => Date::now()->subWeek(),
        ]);
        $wrestler->delete();
        $table = new PreviousWrestlers();
        $table->managerId = $this->manager->id;

        // Act
        $assignments = $table->builder()->get();

        // Assert
        expect($assignments)->toBeEmpty();
    });

    it('resolves eager-loaded wrestlers without additional queries', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()->create();
        WrestlerManager::query()->create([
            'wrestler_id' => $wrestler->id,
            'manager_id' => $this->manager->id,
            'hired_at' => Date::now()->subYear(),
            'fired_at' => Date::now()->subMonth(),
        ]);
        $table = new PreviousWrestlers();
        $table->managerId = $this->manager->id;
        $assignment = $table->builder()->firstOrFail();
        DB::flushQueryLog();
        DB::enableQueryLog();

        // Act
        $renderedWrestler = $table->columns()[0]->resolveValue($assignment);

        // Assert
        expect($renderedWrestler)->toBe($wrestler->name)
            ->and($assignment->relationLoaded('wrestler'))->toBeTrue()
            ->and(DB::getQueryLog())->toBeEmpty();
    });
});

describe('PreviousWrestlers rendering', function (): void {
    it('renders previous wrestler names, dates, and search controls', function (): void {
        // Arrange
        $previousWrestler = Wrestler::factory()->create(['name' => 'Historic Wrestler']);
        $currentWrestler = Wrestler::factory()->create(['name' => 'Current Wrestler']);
        $hiredAt = Date::now()->subMonth();
        $firedAt = Date::now()->subWeek();
        WrestlerManager::query()->create([
            'wrestler_id' => $previousWrestler->id,
            'manager_id' => $this->manager->id,
            'hired_at' => $hiredAt,
            'fired_at' => $firedAt,
        ]);
        WrestlerManager::query()->create([
            'wrestler_id' => $currentWrestler->id,
            'manager_id' => $this->manager->id,
            'hired_at' => Date::now()->subDay(),
            'fired_at' => null,
        ]);

        // Act
        $table = livewire(PreviousWrestlers::class, ['managerId' => $this->manager->id]);

        // Assert
        $table
            ->assertSuccessful()
            ->assertSeeHtml('placeholder="Search wrestlers"')
            ->assertSee('Historic Wrestler')
            ->assertSee($hiredAt->format('Y-m-d'))
            ->assertSee($firedAt->format('Y-m-d'))
            ->assertDontSee('Current Wrestler');
    });

    it('searches previous wrestlers by name', function (): void {
        // Arrange
        foreach (['Historic Wrestler', 'Former Wrestler'] as $offset => $name) {
            $wrestler = Wrestler::factory()->create(['name' => $name]);
            WrestlerManager::query()->create([
                'wrestler_id' => $wrestler->id,
                'manager_id' => $this->manager->id,
                'hired_at' => Date::now()->subMonths($offset + 3),
                'fired_at' => Date::now()->subMonths($offset + 1),
            ]);
        }

        // Act
        $table = livewire(PreviousWrestlers::class, ['managerId' => $this->manager->id]);
        $table->set('search', 'Historic');

        // Assert
        $table
            ->assertSee('Historic Wrestler')
            ->assertDontSee('Former Wrestler');
    });

    it('renders an empty state when the manager has no previous wrestlers', function (): void {
        // Act
        $table = livewire(PreviousWrestlers::class, ['managerId' => $this->manager->id]);

        // Assert
        $table
            ->assertSuccessful()
            ->assertSee('No records found.');
    });
});

describe('PreviousWrestlers authorization', function (): void {
    it('allows administrators to view manager wrestler history', function (): void {
        // Act
        $table = livewire(PreviousWrestlers::class, ['managerId' => $this->manager->id]);

        // Assert
        $table->assertSuccessful();
    });

    it('forbids users without access to the manager', function (string $actor): void {
        // Arrange
        if ($actor === 'guest') {
            Auth::logout();
        } else {
            actingAs(basicUser());
        }

        // Act
        $table = livewire(PreviousWrestlers::class, ['managerId' => $this->manager->id]);

        // Assert
        $table->assertForbidden();
    })->with([
        'guest' => ['guest'],
        'basic user' => ['basic user'],
    ]);
});
