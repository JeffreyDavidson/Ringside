<?php

declare(strict_types=1);

use App\Livewire\Stables\Tables\PreviousWrestlers;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\Stables\StableWrestler;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    actingAs(administrator());
});

describe('PreviousWrestlers query', function (): void {
    it('returns only ended wrestler memberships for the requested stable in newest-first order', function (): void {
        $stable = Stable::factory()->create();

        // Arrange
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

        $table = new PreviousWrestlers;
        $table->stableId = $stable->id;

        // Act
        $memberships = $table->builder()->get();

        // Assert
        expect($memberships->pluck('wrestler_id')->all())->toBe([
            $recentWrestler->id,
            $olderWrestler->id,
        ])->and($memberships->every->relationLoaded('wrestler'))->toBeTrue();
    });
});
