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
    actingAs(administrator());
});

it('requires a stable', function (): void {
    expect(fn () => (new PreviousWrestlers())->builder())
        ->toThrow(LogicException::class, 'A stable was not provided.');
});

it('returns only ended wrestler memberships for the requested stable in newest-first order', function (): void {
    // Arrange
    $stable = Stable::factory()->create();
    $otherStable = Stable::factory()->create();
    $recentWrestler = Wrestler::factory()->create();
    $olderWrestler = Wrestler::factory()->create();
    $currentWrestler = Wrestler::factory()->create();
    $otherWrestler = Wrestler::factory()->create();

    StableWrestler::query()->create([
        'stable_id' => $stable->id,
        'wrestler_id' => $olderWrestler->id,
        'joined_at' => Date::now()->subMonths(4),
        'left_at' => Date::now()->subMonths(3),
    ]);
    StableWrestler::query()->create([
        'stable_id' => $stable->id,
        'wrestler_id' => $recentWrestler->id,
        'joined_at' => Date::now()->subMonths(2),
        'left_at' => Date::now()->subMonth(),
    ]);
    StableWrestler::query()->create([
        'stable_id' => $stable->id,
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
    $table->stableId = $stable->id;

    // Act
    $memberships = $table->builder()->get();

    // Assert
    expect($memberships->pluck('wrestler_id')->all())->toBe([
        $recentWrestler->id,
        $olderWrestler->id,
    ])->and($memberships->every->relationLoaded('wrestler'))->toBeTrue();
});

it('renders previous wrestler links and membership dates', function (): void {
    // Arrange
    $stable = Stable::factory()->create();
    $formerWrestler = Wrestler::factory()->create(['name' => 'Former Wrestler']);
    $currentWrestler = Wrestler::factory()->create(['name' => 'Current Wrestler']);
    $joinedAt = Date::now()->subMonths(3);
    $leftAt = Date::now()->subMonth();

    StableWrestler::query()->create([
        'stable_id' => $stable->id,
        'wrestler_id' => $formerWrestler->id,
        'joined_at' => $joinedAt,
        'left_at' => $leftAt,
    ]);
    StableWrestler::query()->create([
        'stable_id' => $stable->id,
        'wrestler_id' => $currentWrestler->id,
        'joined_at' => Date::now()->subWeek(),
        'left_at' => null,
    ]);

    // Act
    $component = livewire(PreviousWrestlers::class, ['stableId' => $stable->id]);

    // Assert
    $component
        ->assertSuccessful()
        ->assertSee('Former Wrestler')
        ->assertSee(route('wrestlers.show', $formerWrestler))
        ->assertSee($joinedAt->format('Y-m-d'))
        ->assertSee($leftAt->format('Y-m-d'))
        ->assertDontSee('Current Wrestler');
});

it('renders an unknown wrestler when the related wrestler was deleted', function (): void {
    // Arrange
    $stable = Stable::factory()->create();
    $wrestler = Wrestler::factory()->create();
    StableWrestler::query()->create([
        'stable_id' => $stable->id,
        'wrestler_id' => $wrestler->id,
        'joined_at' => Date::now()->subMonth(),
        'left_at' => Date::now()->subWeek(),
    ]);
    $wrestler->delete();

    // Act
    $component = livewire(PreviousWrestlers::class, ['stableId' => $stable->id]);

    // Assert
    $component
        ->assertSuccessful()
        ->assertSee('Unknown');
});

it('forbids users without access to the stable', function (string $actor): void {
    // Arrange
    $stable = Stable::factory()->create();

    if ($actor === 'guest') {
        Auth::logout();
    } else {
        actingAs(basicUser());
    }

    // Act
    $component = livewire(PreviousWrestlers::class, ['stableId' => $stable->id]);

    // Assert
    $component->assertForbidden();
})->with([
    'guest' => ['guest'],
    'basic user' => ['basic user'],
]);
