<?php

declare(strict_types=1);

use App\Livewire\Stables\Tables\PreviousWrestlers;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\Stables\StableWrestler;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->stable = Stable::factory()->create();
    actingAs(administrator());
});

describe('PreviousWrestlers configuration', function (): void {
    it('requires a stable', function (): void {
        // Act & Assert
        expect(fn () => (new PreviousWrestlers())->builder())
            ->toThrow(LogicException::class, 'A stable was not provided.');
    });
});

describe('PreviousWrestlers query', function (): void {
    it('returns only ended wrestler memberships for the requested stable in newest-first order', function (): void {
        // Arrange
        $otherStable = Stable::factory()->create();
        $recentWrestler = Wrestler::factory()->create();
        $olderWrestler = Wrestler::factory()->create();
        $currentWrestler = Wrestler::factory()->create();
        $otherWrestler = Wrestler::factory()->create();

        StableWrestler::query()->create([
            'stable_id' => $this->stable->id,
            'wrestler_id' => $olderWrestler->id,
            'joined_at' => Date::now()->subMonths(4),
            'left_at' => Date::now()->subMonths(3),
        ]);
        StableWrestler::query()->create([
            'stable_id' => $this->stable->id,
            'wrestler_id' => $recentWrestler->id,
            'joined_at' => Date::now()->subMonths(2),
            'left_at' => Date::now()->subMonth(),
        ]);
        StableWrestler::query()->create([
            'stable_id' => $this->stable->id,
            'wrestler_id' => $currentWrestler->id,
            'joined_at' => Date::now()->subWeek(),
            'left_at' => null,
        ]);
        StableWrestler::query()->create([
            'stable_id' => $otherStable->id,
            'wrestler_id' => $otherWrestler->id,
            'joined_at' => Date::now()->subDays(3),
            'left_at' => Date::now()->subDay(),
        ]);

        $table = new PreviousWrestlers();
        $table->stableId = $this->stable->id;

        // Act
        $memberships = $table->builder()->get();

        // Assert
        expect($memberships->pluck('wrestler_id')->all())->toBe([
            $recentWrestler->id,
            $olderWrestler->id,
        ])->and($memberships->every->relationLoaded('wrestler'))->toBeTrue();
    });
});

describe('PreviousWrestlers rendering', function (): void {
    it('renders previous wrestler links, membership dates, and search controls', function (): void {
        // Arrange
        $formerWrestler = Wrestler::factory()->create(['name' => 'Former Wrestler']);
        $currentWrestler = Wrestler::factory()->create(['name' => 'Current Wrestler']);
        $joinedAt = Date::now()->subMonths(3);
        $leftAt = Date::now()->subMonth();

        StableWrestler::query()->create([
            'stable_id' => $this->stable->id,
            'wrestler_id' => $formerWrestler->id,
            'joined_at' => $joinedAt,
            'left_at' => $leftAt,
        ]);
        StableWrestler::query()->create([
            'stable_id' => $this->stable->id,
            'wrestler_id' => $currentWrestler->id,
            'joined_at' => Date::now()->subWeek(),
            'left_at' => null,
        ]);

        // Act
        $table = livewire(PreviousWrestlers::class, ['stableId' => $this->stable->id]);

        // Assert
        $table
            ->assertSuccessful()
            ->assertSeeHtml('placeholder="Search wrestlers"')
            ->assertSee('Former Wrestler')
            ->assertSee(route('wrestlers.show', $formerWrestler))
            ->assertSee($joinedAt->format('Y-m-d'))
            ->assertSee($leftAt->format('Y-m-d'))
            ->assertDontSee('Current Wrestler');
    });

    it('searches previous wrestlers by name', function (): void {
        // Arrange
        foreach (['Historic Wrestler', 'Former Wrestler'] as $offset => $name) {
            $wrestler = Wrestler::factory()->create(['name' => $name]);
            StableWrestler::query()->create([
                'stable_id' => $this->stable->id,
                'wrestler_id' => $wrestler->id,
                'joined_at' => Date::now()->subMonths($offset + 3),
                'left_at' => Date::now()->subMonths($offset + 1),
            ]);
        }

        // Act
        $table = livewire(PreviousWrestlers::class, ['stableId' => $this->stable->id]);
        $table->set('search', 'Historic');

        // Assert
        $table
            ->assertSee('Historic Wrestler')
            ->assertDontSee('Former Wrestler');
    });

    it('renders an unknown wrestler when the related wrestler was deleted', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()->create();
        StableWrestler::query()->create([
            'stable_id' => $this->stable->id,
            'wrestler_id' => $wrestler->id,
            'joined_at' => Date::now()->subMonth(),
            'left_at' => Date::now()->subWeek(),
        ]);
        $wrestler->delete();

        // Act
        $table = livewire(PreviousWrestlers::class, ['stableId' => $this->stable->id]);

        // Assert
        $table
            ->assertSuccessful()
            ->assertSee('Unknown');
    });

    it('renders an empty state when the stable has no previous wrestlers', function (): void {
        // Act
        $table = livewire(PreviousWrestlers::class, ['stableId' => $this->stable->id]);

        // Assert
        $table
            ->assertSuccessful()
            ->assertSee('No records found.');
    });
});

describe('PreviousWrestlers authorization', function (): void {
    it('allows administrators to view stable wrestler history', function (): void {
        // Act
        $table = livewire(PreviousWrestlers::class, ['stableId' => $this->stable->id]);

        // Assert
        $table->assertSuccessful();
    });

    it('forbids users without access to the stable', function (string $actor): void {
        // Arrange
        if ($actor === 'guest') {
            Auth::logout();
        } else {
            actingAs(basicUser());
        }

        // Act
        $table = livewire(PreviousWrestlers::class, ['stableId' => $this->stable->id]);

        // Assert
        $table->assertForbidden();
    })->with([
        'guest' => ['guest'],
        'basic user' => ['basic user'],
    ]);
});
